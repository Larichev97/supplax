<?php

namespace AppBundle\Controller\CRM;

use AppBundle\Controller\TwilioBaseController;
use AppBundle\Controller\TwilioConfig;
use AppBundle\Entity\CRM\DialogDeal;
use AppBundle\Entity\CRM\DialogDealStatusHistory;
use AppBundle\Entity\CRM\DialogEstimate;
use AppBundle\Entity\CRM\DialogParams;
use AppBundle\Entity\CRM\Sms;
use AppBundle\Entity\CRM\UserActivity;
use AppBundle\Entity\User\User;
use AppBundle\Service\CRM\ChatService;
use AppBundle\Service\MiniSmsService;
use AppBundle\Service\TextService;
use AppBundle\Traits\MemcachedTrait;
use DateTime;
use DateTimeZone;
use Exception;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DealsController extends TwilioBaseController
{
    use MemcachedTrait;

    /**
     * @param Request $request
     * @param DateTime|null $periodStart
     * @param DateTime|null $periodEnd
     * @return array
     * @throws Exception
     */
    protected function getDeals(Request $request, ?DateTime $periodStart = null, ?DateTime $periodEnd = null)
    {
        $twUser = [];
        if ($sysUser = $this->getUser()) {
            $twUser = TwilioConfig::getTwUser($this->containerAware, $sysUser->getId());
        }

        // ...code removed...

        $tableData = [
            'draw' => (int)$request->get('draw', 1),
            'recordsTotal' => $totalCount,
            'recordsFiltered' => $filterCount,
            'data' => $data,
            'totalAmount' => $amountProfitTotal,
            'pageAmount' => $amountProfitPage,
        ];

        asort($masterList);
        return [
            $dealList,
            $tableData,
            $filters,
            $commonStatuses,
            $dropStatusesToForm,
            $estimateStatuses,
            $reviewStatusesName,
            $miniSms->getUserListSorted($userListAuthorActive, $userListAuthorFired),
            $miniSms->getUserListSorted($userListRespActive, $userListRespFired),
            $masterList,
            $helperList,
            $pmList
        ];
    }

    /**
     * @Route("/crm/deals", name="twilio_crm_deals")
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function dealsAction(Request $request): Response
    {
        /** @var User $sysUser */
        try {
            [$sysUser, $twUser] = $this->checkRight($request, false, ['page']);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
        }
        [
            $dealList,
            $tableData,
            $filters,
            $commonStatuses,
            $dropStatuses,
            $estimateStatuses,
            $reviewStatusesName,
            $userListAuthor,
            $userListResponsible,
            $masterList,
            $helperList,
            $pmList,
        ] = $this->getDeals($request);
        // Set dropdowns
        $dropdowns = [];
        $output = [];

        if ($request->isXmlHttpRequest()) {
            if (($tableData['data'] ?? []) && $tableData['data']) {
                foreach ($tableData['data'] as $deal) {
                    $timeZoneLocal = new DateTimeZone($deal['timeZone'] ?: TwilioConfig::DEFAULT_TIME_ZONE);
                    $dealLink = '<a class="action-icon" href="' . $deal['link'] . '" target="_blank"><i class="mdi mdi-wechat"></i></a>';
                    $actionIcons = '';
                    $typeBadge = '';
                    if ($deal['type'] === 'deal') {
                        $typeBadge = '<span class="badge bg-info text-light status-' . $deal['type'] . '-' . $deal['statusId'] . '" title="Deal">Deal</span>';
                        $actionIcons .= '<a class="action-icon show-history" href="#" data-id="' . $deal['id'] . '" data-url="' . $deal['link'] . '"
                                 data-phone1="' . $deal['ourPhone'] . '" data-phone2="' . $deal['fakeClientPhone'] . '"><i class="mdi mdi-file-document"></i></a>';
                    } elseif ($deal['type'] === 'estimate') {
                        $typeBadge = '<span class="badge bg-success text-light status-' . $deal['type'] . '-' . $deal['statusId'] . '" title="Estimate">Est</span>';
                    }
                    $masterLink = '';
                    if ($deal['master']) {
                        $masterLink = $this->containerAware->generateUrl('twilio_crm_masters_view',
                            ['masterId' => $deal['masterId']]);
                    }
                    $dealDateDiv = '';
                    if ($deal['dealTimeAt']) {
                        $dealDateLocal = clone $deal['dealTimeAt'];
                        $dealDateLocal->setTimezone($timeZoneLocal);
                        $dealDate = $deal['dealTimeAt']->setTimezone(TwilioConfig::getTimezoneDefault());
                        $dealDateDiv = '<div title="' . $dealDateLocal->format('d.m.Y H:i T') . '">' . $dealDate->format('d.m.Y H:i') . '</div>';
                    }
                    $dealPaidDiv = '';
                    if ($deal['dealPaidAt']) {
                        $dealPaidDateLocal = clone $deal['dealPaidAt'];
                        $dealPaidDateLocal->setTimezone($timeZoneLocal);
                        $dealPaidDate = $deal['dealPaidAt']->setTimezone(TwilioConfig::getTimezoneDefault());
                        $dealPaidDiv = '<div title="' . $dealPaidDateLocal->format('d.m.Y H:i T') . '">' . $dealPaidDate->format('d.m.Y H:i') . '</div>';
                    }
                    $leadCreateDateDiv = '';
                    if ($deal['leadCreateAt']) {
                        $leadCreateDateLocal = clone $deal['leadCreateAt'];
                        $leadCreateDateLocal->setTimezone($timeZoneLocal);
                        $leadCreateDate = $deal['leadCreateAt']->setTimezone(TwilioConfig::getTimezoneDefault());
                        $leadCreateDateDiv = '<div title="' . $leadCreateDateLocal->format('d.m.Y H:i T') . '">' . $leadCreateDate->format('d.m.Y H:i') . '</div>';
                    }
                    $dateCreateDiv = '';
                    if ($deal['dealCreateAt']) {
                        $dateCreateLocal = clone $deal['dealCreateAt'];
                        $dateCreateLocal->setTimezone($timeZoneLocal);
                        $dateCreate = $deal['dealCreateAt']->setTimezone(TwilioConfig::getTimezoneDefault());
                        $dateCreateDiv = '<div title="' . $dateCreateLocal->format('d.m.Y H:i T') . '">' . $dateCreate->format('d.m.Y H:i') . '</div>';
                    }
                    $dealInvoiceAtDiv = '';
                    if ($deal['dealInvoiceStatusAt']) {
                        $dealInvoiceAtLocal = clone $deal['dealInvoiceStatusAt'];
                        $dealInvoiceAtLocal->setTimezone($timeZoneLocal);
                        $dealInvoiceAt = $deal['dealInvoiceStatusAt']->setTimezone(TwilioConfig::getTimezoneDefault());
                        $dealInvoiceAtDiv = '<div title="' . $dealInvoiceAtLocal->format('d.m.Y H:i T') . '">' . $dealInvoiceAt->format('d.m.Y H:i') . '</div>';
                    }

                    $dealInvoice = '';
                    if ($deal['invoice']) {
                        $dealInvoice = ' <a href="' . $deal['invoice'] . '" target="_blank" data-original-title="'
                            . $deal['invoice'] . '" class="action-icon"><i class="mdi mdi-cash-usd"></i></a>';
                    }

                    $output[] = [
                        '<a class="' . $deal['type'] . '-change" href="" data-id="' . $deal['id'] . '">' . $deal['id'] . '</a>',
                        $typeBadge,
                        $dealDateDiv,
                        $dealPaidDiv,
                        '<span class="' . $deal['type'] . '-status-' . $deal['statusId'] . '">' . $deal['status'] . '</span>',
                        $deal['feedback'],
                        $deal['city'],
                        $deal['work'],
                        $deal['hasTask'] ? 'Yes' : 'No',
                        $deal['responsible'],
                        $deal['authorName'],
                        $deal['masterId'] ? '<a target="_blank" href="' . $masterLink . '">'
                            . $deal['master'] . '</a><span class="master-dialogs" data-id="' . $deal['masterId'] . '"><i class="mdi mdi-wechat"></i></span>' : 'Not set',
                        $deal['helperName'],
                        $deal['pmName'],
                        $deal['source'],
                        trim($deal['clientName'] . ' ' . $dealLink),
                        $deal['amount'] ? $deal['amount'] . '$' : '',
                        $deal['profit'] ? $deal['profit'] . '$ ' : '',
                        $deal['commission'],
                        $deal['param'] ?? '',
                        $leadCreateDateDiv,
                        $dateCreateDiv,
                        $dealInvoiceAtDiv,
                        $dealInvoice,
                        $actionIcons,
                    ];
                }
            }
            $tableData['data'] = $output ?? [];
            return $this->json($tableData);
        }

        $dropdowns[0] = [];
        $dropdowns[0][] = [
            'id' => 'f-status',
            'name' => 'Status',
            'placeholder' => 'Choose status...',
            'grouped' => true,
            'values' => [
                'For All' => [
                    'c1' => 'Open deal',
                    'c2' => 'Closed deal',
                    'c3' => 'No status',
                    'c4' => 'Lost deals',
                    'c5' => 'Estimates',
                    'c6' => 'All deals',
                ],
                'Common' => $commonStatuses,
                'Feedback' => $reviewStatusesName,
                'Drop' => $dropStatuses,
                'Estimate' => $estimateStatuses,
            ],
        ];

        $dropdowns[1] = [];

        $cities = array_combine(TwilioConfig::CITIES, TwilioConfig::CITIES);
        asort($cities);
        $cities = ['without' => 'Without city'] + $cities;
        $dropdowns[1][] = [
            'id' => 'f-city',
            'name' => 'City',
            'placeholder' => 'Choose city...',
            'values' => $cities,
        ];

        $workTypes = TwilioConfig::$workTypes;
        asort($workTypes);
        $workTypes = ['without' => 'Without work'] + $workTypes;

        $sources = TwilioConfig::FILTER_SOURCES;
        $sources = ['without' => 'Without source'] + $sources;

        $dropdowns[1][] = [
            'id' => 'f-work',
            'name' => 'WorkTypes',
            'placeholder' => 'Choose work type...',
            'values' => $workTypes,
        ];

        $dropdowns[1][] = [
            'id' => 'f-source',
            'name' => 'dialogSource',
            'placeholder' => 'Choose dialog source...',
            'values' => $sources,
        ];

        $dropdowns[1][] = [
            'id' => 'f-master',
            'name' => 'Masters',
            'placeholder' => 'Choose master...',
            'values' => [
                    -2 => 'Without master',
                    -1 => 'With any master'
                ] + $masterList,
        ];
        $ratingArray = [];
        for ($i = 1; $i <= 10; $i++) {
            $ratingArray[$i] = $i;
        }
        $dropdowns[1][] = [
            'id' => 'f-rating',
            'name' => 'Rating',
            'placeholder' => 'Choose rating...',
            'values' => [
                    -2 => 'Without rating',
                    -1 => 'With any rating'
                ] + $ratingArray,
        ];

        $dropdowns[2] = [];

        $dropdowns[2][] = [
            'id' => 'f-salesman',
            'name' => 'Sales',
            'placeholder' => 'Choose responsible...',
            'values' => [
                    -2 => 'Without responsible',
                    -1 => 'With any responsible',
                ] + $userListResponsible,
        ];

        $dropdowns[2][] = [
            'id' => 'f-author',
            'name' => 'Author',
            'placeholder' => 'Choose deal author...',
            'values' => [
                    -2 => 'Without author',
                    -1 => 'With any author',
                ] + $userListAuthor,
        ];

        $dropdowns[2][] = [
            'id' => 'f-helper',
            'name' => 'Helper',
            'placeholder' => 'Choose helper...',
            'values' => [
                    -2 => 'Without helper',
                    -1 => 'With any helper',
                ] + $helperList,
        ];

        $dropdowns[2][] = [
            'id' => 'f-pm',
            'name' => 'PM',
            'placeholder' => 'Choose PM...',
            'values' => [
                    -2 => 'Without PM',
                    -1 => 'With any PM',
                ] + $pmList,
        ];

        $defTimeZone = TwilioConfig::DEFAULT_TIME_ZONE;

        $isWatchDeals = ($twUser['pages'] ?? false) && in_array('twilio_crm_weekend_deals', $twUser['pages'], true);
        $dealsAndEstimates = $dealList;
        $dealsStatusColors = DialogDeal::$statusColors;
        $dealsStatusesName = DialogDeal::$statusName;
        $dealsStatusesName[11] = 'Lost: Sales (old)';
        $dealsStatusesName[8] = 'Lost';
        $estimatesStatusColors = DialogEstimate::$statusColors;
        $estimatesStatusNames = DialogEstimate::$statusName;


        return $this->render('AppBundle:Front:Twilio/deals/list.html.twig', [
            'deals' => $dealList,
            'dealsAndEstimate' => $dealsAndEstimates,
            'filters' => $filters,
            'dropdowns' => $dropdowns,
            'timeZone' => $defTimeZone,
            'isWatchDeals' => $isWatchDeals,
            'dealsStatusColors' => $dealsStatusColors,
            'dealsStatusNames' => $dealsStatusesName,
            'estimatesStatusColors' => $estimatesStatusColors,
            'estimatesStatusNames' => $estimatesStatusNames,
        ]);
    }

    /**
     * @Route("/crm/deals/history", name="twilio_crm_deals_history")
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function dealsHistoryAction(Request $request): Response
    {
        /** @var User $sysUser */
        try {
            [$sysUser, $twUser] = $this->checkRight(
                $request,
                true,
                ['page'],
                UserActivity::TYPE_COMMON,
                'twilio_crm_deals'
            );
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
        }

        try {
            [$ourPhone, $clientPhone] = $this->getRequestPhones($request);
        } catch (RuntimeException $e) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
        }

        if ($twUser['group'] !== 'admins') {
            $skip = true;
            $phoneRoutes = TwilioConfig::getPhoneRoutesStatic($this->containerAware, $ourPhone);
            foreach ($phoneRoutes as $phoneRoute) {
                if (array_key_exists($phoneRoute, $twUser['miniSMS'])) {
                    $skip = false;
                    break;
                }
            }
            if ($skip) {
                return $this->json(['error' => 'You do not have permission to this feature']);
            }
        }

        $smss = $this->containerAware->getCrmSmsRepo()->getNonActionSmsByNumbers($ourPhone, $clientPhone);
        if (!$smss) {
            return $this->json(['error' => 'No history']);
        }

        $html = $this->renderView('AppBundle:Front:Twilio/deals/history.html.twig', [
            'smss' => $smss,
            'currentUser' => $twUser,
        ]);

        return $this->json(['html' => $html]);
    }

    /**
     * @Route("/crm/deals/calendar.json", name="twilio_crm_deals_calendar_json")
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function dealsCalendarAction(Request $request): Response
    {
        /** @var User $sysUser */
        try {
            [$sysUser, $twUser] = $this->checkRight(
                $request,
                false,
                ['page'],
                UserActivity::TYPE_HISTORY,
                'twilio_crm_deals'
            );
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
        }

        // ...code removed...

        return $this->json($return);
    }

    /**
     * @Route("/sms-mini/deal/list", name="twilio_crm_deal_list")
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function dialogDealList(Request $request): Response
    {
        /** @var User $sysUser */
        try {
            [$sysUser, $twUser] = $this->checkRight($request, true);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
        }

        try {
            [$ourPhone, $clientPhone] = $this->getRequestPhones($request);
        } catch (RuntimeException $e) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
        }
        $ourPhoneCity = $this->containerAware->getMiniSmsService()->getCityDialog($ourPhone, $clientPhone);
        $timeZoneString = TwilioConfig::CITY_PHONE_CODES[$ourPhoneCity]['timeZone'] ?? TwilioConfig::DEFAULT_TIME_ZONE;
        $timeZone = new DateTimeZone($timeZoneString);
        $deals = $this->containerAware->getDialogDealRepo()->getAllByPhones($ourPhone, $clientPhone);
        $estimates = $this->containerAware->getDialogEstimateRepo()->getAllByPhones($ourPhone, $clientPhone);
        $dialogParams = $this->containerAware->getDialogParamsRepo()->getByPhones($ourPhone, $clientPhone);
        if ($dialogParams) {
            $address = $dialogParams->getAddress() ?: '';
        }
        $feedbackData = [];
        if ($deals) {
            foreach ($deals as $deal) {
                if ($deal->getStatus() !== DialogDeal::STATUS_FEEDBACK_PROCESSED
                    && $deal->getStatus() !== DialogDeal::STATUS_FEEDBACK_RECEIVED) {
                    continue;
                }
                $dealId = $deal->getId();
                $rating = $deal->getDealRating();
                $feedbackData[$dealId] = [];
                switch ($rating) {
                    case 0:
                        $feedbackData[$dealId]['ratingBadge'] = 'badge-light';
                        break;
                    case ($rating > 0 && $rating <= 3):
                        $feedbackData[$dealId]['ratingBadge'] = 'badge-danger';
                        break;
                    case ($rating > 3 && $rating <= 5):
                        $feedbackData[$dealId]['ratingBadge'] = 'badge-secondary';
                        break;
                    case ($rating > 5 && $rating <= 7):
                        $feedbackData[$dealId]['ratingBadge'] = 'badge-warning';
                        break;
                    case ($rating > 7):
                        $feedbackData[$dealId]['ratingBadge'] = 'badge-success';
                        break;

                }

                $feedbackData[$dealId]['reviewBadge'] = $deal->getReviewStatus() === DialogDeal::REVIEW_STATUS_BAD_WORK ? 'badge-danger' : 'badge-success';
            }
        }
        $json = [
            'html' => $this->renderView('AppBundle:Front:Twilio/deals/dialog-list.html.twig', [
                'deals' => $deals,
                'estimates' => $estimates,
                'ourPhone' => $ourPhone,
                'clientPhone' => $clientPhone,
                'timeZone' => $timeZone,
                'address' => $address ?? '',
                'feedbackData' => $feedbackData,
                'currentUser' => $twUser,
            ]),
        ];
        return $this->json($json);
    }

    /**
     * @Route("/sms-mini/deal/form", name="twilio_crm_deal_form")
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function dialogDealForm(Request $request): Response
    {
        /** @var User $sysUser */
        try {
            [$sysUser, $twUser] = $this->checkRight($request, true);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
        }
        $deal = null;
        $dealCreate = false;
        if ($dealId = (int)$request->get('id', 0)) {
            $deal = $this->containerAware->getDialogDealRepo()->getById($dealId);
            if (!$deal) {
                return $this->getErrorRequest($request->isXmlHttpRequest(), 'Deal not found');
            }
            $ourPhone = $deal->getOurNumber();
            $clientPhone = $deal->getClientNumber();
        } else {
            try {
                [$ourPhone, $clientPhone] = $this->getRequestPhones($request);
                $dealCreate = true;
            } catch (RuntimeException $e) {
                return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
            }
        }
        $ourPhoneCity = $this->containerAware->getMiniSmsService()->getCityDialog($ourPhone, $clientPhone);
        $dialogGroup = $this->containerAware->getMiniSmsService()->getGroupDialog($ourPhone, $clientPhone);

        if (!TwilioConfig::isAccessGroup($twUser, $dialogGroup)) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), 'Deal not found');
        }

        if ($dealCreate
            && ($dialogGroup === 'epoxy' || $dialogGroup === 'wallpaper')
        ) {
            $emptyFields = $this->containerAware->getDealService()->checkingDialogForEmptyDescriptionFields(
                $ourPhone,
                $clientPhone
            );
            if ($emptyFields) {
                return $this->getErrorRequest(
                    $request->isXmlHttpRequest(),
                    'Please fill all lead description fields before create new deals');
            }

        }
        $timeZone = new DateTimeZone(TwilioConfig::CITY_PHONE_CODES[$ourPhoneCity]['timeZone'] ?? TwilioConfig::DEFAULT_TIME_ZONE);
        $cityDefault = $ourPhoneCity ?: 'LosAngeles';
        $isInvoiceEdit = $twUser['group'] === 'admins' || in_array('twilio_crm_deals_invoice', $twUser['pages'], true);
        $dealStatuses = $this->containerAware->getMiniSmsService()->getDealStatuses($ourPhone);
        $disabledDealStatuses = $this->containerAware->getDealService()->getDisabledDealStatuses($twUser, $deal);
        $dealMasters = $this->containerAware->getMiniSmsService()->getMasterList(
            $ourPhoneCity,
            $this->containerAware->getMiniSmsService()->getGroupDialog($ourPhone, $clientPhone)
        );

        if ($deal || $this->containerAware->getDialogDealRepo()->getLastByPhones($ourPhone, $clientPhone)) {
            $defaultAmount = '';
            $defaultProfit = '';
        } else {
            /** @noinspection ProperNullCoalescingOperatorUsageInspection */
            $defaultAmount = $this->containerAware->getMiniSmsService()->getDialogAmount($ourPhone, $clientPhone) ?? '';
            /** @noinspection ProperNullCoalescingOperatorUsageInspection */
            $defaultProfit = $this->containerAware->getMiniSmsService()->getDialogProfit($ourPhone, $clientPhone) ?? '';
        }
        $reviewStatuses = DialogDeal::$reviewStatusesName;
        $showReviewFields = false;
        if ($deal && $deal->getStatus()) {
            $dealCurrentStatus = $deal->getStatus();
            if ($deal->getReviewStatus() !== 0
                || $deal->getDealRating() !== 0
                || $dealCurrentStatus === DialogDeal::STATUS_FEEDBACK_RECEIVED
                || $dealCurrentStatus === DialogDeal::STATUS_FEEDBACK_PROCESSED
            ) {
                $showReviewFields = true;
            }
        }
        $canDeleteDeals = false;
        $canEditPaidDate = false;
        if (($twUser['canDeleteDeals'] ?? false) || $twUser['group'] === 'admins') {
            $canDeleteDeals = true;
        }
        if (($twUser['canEditPaidDate'] ?? false) || $twUser['group'] === 'admins') {
            $canEditPaidDate = true;
        }
        if ($deal && array_key_exists($deal->getStatus(), DialogDeal::$unusedDealStatuses)) {
            $dealStatuses[$deal->getStatus()] = DialogDeal::$unusedDealStatuses[$deal->getStatus()];
        }
        $canChangeProfit = false;
        if ($twUser['group'] === 'admins' || $twUser['group'] === 'head sales') {
            $canChangeProfit = true;
        }

        [$dealCount] = $this->containerAware->getMiniSmsService()->getDealCount($ourPhone, $clientPhone);

        $json = [
            'html' => $this->renderView('AppBundle:Front:Twilio/deals/dialog-form.html.twig', [
                'deal' => $deal,
                'dealStatuses' => $dealStatuses,
                'dealMasters' => $dealMasters,
                'ourPhone' => $ourPhone,
                'clientPhone' => $clientPhone,
                'timeZone' => $timeZone,
                'city' => $cityDefault,
                'isInvoiceEdit' => $isInvoiceEdit,
                'defaultAmount' => $defaultAmount,
                'defaultProfit' => $defaultProfit,
                'reviewStatuses' => $reviewStatuses,
                'showReviewFields' => $showReviewFields,
                'canEditPaidDate' => $canEditPaidDate,
                'disabledDealStatuses' => $disabledDealStatuses,
                'dealLostStatuses' => DialogDeal::$statusesLost,
                'canChangeProfit' => $canChangeProfit,
                'canChangeProfitStatuses' => DialogDeal::$canChangeProfitStatuses,
                'dealParams' => DialogDeal::$paramsData,
                'dealCount' => $dealCount,
                'paramDefaultWithDeals' => DialogDeal::PARAMS_DATA_DEFAULT_WITH_DEAL,
                'paramDefaultWithoutDeals' => DialogDeal::PARAMS_DATA_DEFAULT_WITHOUT_DEAL
            ]),
            'canDeleteDeals' => $canDeleteDeals,
        ];
        return $this->json($json);
    }

    /**
     * @Route("/sms-mini/deal/create", name="twilio_sms_deal_create")
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function smsMiniDealCreateAction(Request $request): Response
    {
        /** @var User $sysUser */
        try {
            [$sysUser, $twUser] = $this->checkRight($request, true);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
        }

        $deal = null;
        if ($dealId = (int)$request->get('deal-id', 0)) {
            $deal = $this->containerAware->getDialogDealRepo()->getById($dealId);
            if (!$deal) {
                return $this->getErrorRequest($request->isXmlHttpRequest(), 'Deal not found');
            }
            $ourPhone = $deal->getOurNumber();
            $clientPhone = $deal->getClientNumber();
        } else {
            try {
                [$ourPhone, $clientPhone] = $this->getRequestPhones($request);
            } catch (RuntimeException $e) {
                return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
            }
        }


        $ourPhoneCity = $this->containerAware->getMiniSmsService()->getCityDialog($ourPhone, $clientPhone);
        $ourPhoneGroup = $this->containerAware->getMiniSmsService()->getGroupDialog($ourPhone, $clientPhone);
        $updateMasterDeals = false;
        $updateMasterIds = [];

        $amount = $request->get('deal-amount', '');
        if ($amount === '') {
            $amount = null;
        } else {
            $amount = (int)$amount;
            if ($amount < 0) {
                return $this->getErrorRequest($request->isXmlHttpRequest(), 'Amount cannot be negative');
            }
        }

        $profit = $request->get('deal-profit', '');
        if ($profit === '') {
            $profit = null;
        } else {
            $profit = (int)$profit;
            if ($profit < 0) {
                return $this->getErrorRequest($request->isXmlHttpRequest(), 'Profit cannot be negative');
            }
        }

        $isInvoiceEdit = $twUser['group'] === 'admins' || in_array('twilio_crm_deals_invoice', $twUser['pages'], true);
        $disabledDealStatuses = $this->containerAware->getDealService()->getDisabledDealStatuses($twUser, $deal);
        $invoice = trim($request->get('dialog-invoice', ''));
        if (!$invoice) {
            $invoice = null;
        }

        $address = trim($request->get('dialog-address', '')) ?: null;
        $comment = trim($request->get('dialog-description', ''));
        //if (!$comment) {
        //    return $this->json(['error' => 'Enter comment']);
        //}

        $master = null;
        $masters = $this->containerAware->getMiniSmsService()->getMasterList(
            $ourPhoneCity,
            $ourPhoneGroup
        );
        if ($masterId = (int)$request->get('deal-master', 0)) {
            if (!array_key_exists($masterId, $masters)) {
                return $this->getErrorRequest($request->isXmlHttpRequest(), 'Incorrect master');
            }
            $master = $this->containerAware->getMasterRepo()->getById($masterId);
            if (!$master) {
                return $this->getErrorRequest($request->isXmlHttpRequest(), 'Master not found');
            }
        }

        $status = (int)$request->get('deal-status', 0);
        $statuses = $this->containerAware->getMiniSmsService()->getDealStatuses($ourPhone);
        if (!array_key_exists($status, $statuses)
            && array_key_exists($status, DialogDeal::$unusedDealStatuses)) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), 'Unknown status');
        }
        $reviewRating = (int)$request->get('review-rating', 0);
        $reviewStatus = (int)$request->get('review-status', 0);
        $canChangeProfit = true;
        if ($twUser['group'] !== 'admins'
            && $twUser['group'] !== 'head sales'
            && !in_array($status, DialogDeal::$canChangeProfitStatuses, true)
        ) {
            $canChangeProfit = false;
        }

        $timeZone = new DateTimeZone(TwilioConfig::CITY_PHONE_CODES[$ourPhoneCity]['timeZone']
            ?? TwilioConfig::DEFAULT_TIME_ZONE);
//        $timeZone = new DateTimeZone($ourPhoneConfig['timeZone'] ?? TwilioConfig::DEFAULT_TIME_ZONE);
        $timeZoneUTC = new DateTimeZone(date_default_timezone_get());
        $dateInput = trim($request->get('deal-date', ''));
        $timeInput = trim($request->get('deal-time', ''));
        $dealDate = null;
        $dealDateUTC = null;
        if ($dateInput && $timeInput) {
            $dateTimeStr = $dateInput . ' ' . $timeInput;
            try {
                $dealDate = new DateTime($dateTimeStr, $timeZone);
                if ($dealDate) {
                    $dealDateUTC = clone $dealDate;
                    $dealDateUTC->setTimezone($timeZoneUTC);
                } else {
                    $dealDate = null;
                }
            } catch (Exception $e) {
                $dealDate = null;
            }
        }
        $param = TextService::correctInput($request->get('deal-params', ''), 255);
        $isCreate = true;
        $changeRating = false;
        if ($deal) { // update deal
            $isCreate = false;
            // deal date
            $dealDateOld = $deal->getDealAt();
            if ($dealDateOld != $dealDateUTC) {
                $prevDeal = clone $deal;
                $deal->setDealAt($dealDateUTC);
                $message = 'Deal #' . $deal->getId() . ' date changed'
                    . ($dealDateOld ? (' from '
                        . $dealDateOld->setTimezone($timeZone)->format('d.m.Y H:i')) : '')
                    . ' to ' . $dealDate->format('d.m.Y H:i') . ' (' . $ourPhoneCity . ')';
                $sms = (new Sms())
                    ->setType(Sms::TYPE_DEAL_DATE)
                    ->setDirection(Sms::DIRECTION_NO)
                    ->setSendAt(new DateTime())
                    ->setAuthor($sysUser)
                    ->setSenderPhone($ourPhone)
                    ->setRecipientPhone($clientPhone)
                    ->setBody($message);
                $this->containerAware->saveEntity($sms);
                $this->containerAware->getEntityChangesService()->checkObjectDifference(
                    $sysUser,
                    $request,
                    $prevDeal,
                    $deal
                );
            }

            // status
            $changePaidAt = true;
            $changeStatus = false;
            if ($deal->getStatus() !== $status) {
                $changeStatus = true;
                $statusNameOld = $deal->getStatusName();
                if (in_array($status, $disabledDealStatuses, true)) {
                    return $this->json(['error' => 'Forbidden status']);
                }
                if (!$deal->getPaidAt()
                    && in_array($status, DialogDeal::$successfullyCompletedDeal)
                ) {
                    $deal->setPaidAt(new DateTime());
                    $changePaidAt = false;
                }
                if (($status === DialogDeal::STATUS_INVOICE || $status === DialogDeal::STATUS_GROUP_INVOICE)
                    && !$deal->getInvoiceStatusAt()
                ) {
                    $deal->setInvoiceStatusAt(new DateTime());
                    $changePaidAt = false;
                }
                if ($status === DialogDeal::STATUS_DEAL_APPROVED) {
                    $respUser = $this->containerAware->getCrmSmsRepo()->getResponsibleUser($ourPhone, $clientPhone);
                    $author = $respUser ?: $sysUser;
                    $this->containerAware->getMiniSmsService()->createNewTask(
                        $ourPhone,
                        $clientPhone,
                        $author,
                        $author,
                        'Notify the master about the deal #' . $deal->getId(),
                        new DateTime(),
                        $this->isDev()
                    );
                }
                $lostComment = '';
                if (in_array($status, DialogDeal::$statusesLost, true)) {
                    $lostComment = trim($request->get('lost-comment', ''));
                    $deal->setLostComment($lostComment);
                }
                $deal->setStatus($status);
                $statusName = $deal->getStatusName();
                $message = 'Deal #' . $deal->getId() . ' status changed from ' . $statusNameOld . ' to ' . $statusName;
                if ($lostComment) {
                    $message .= PHP_EOL . $lostComment;
                }
                $sms = (new Sms())
                    ->setType(Sms::TYPE_DEAL_STATUS)
                    ->setDirection(Sms::DIRECTION_NO)
                    ->setSendAt(new DateTime())
                    ->setAuthor($sysUser)
                    ->setSenderPhone($ourPhone)
                    ->setRecipientPhone($clientPhone)
                    ->setBody($message);
                $this->containerAware->saveEntity($sms);
                $dialogDealStatusHistory = (new DialogDealStatusHistory())
                    ->setAuthor($sysUser)
                    ->setDeal($deal)
                    ->setStatus($status)
                    ->setAmount($deal->getAmount())
                    ->setProfit($deal->getProfit());
                $this->containerAware->saveEntity($dialogDealStatusHistory);
                if ($masterId) {
                    $updateMasterDeals = true;
                    $updateMasterIds[$masterId] = $masterId;
                }
            } else {
                if (in_array($status, DialogDeal::$statusesLost, true)) {
                    $lostComment = trim($request->get('lost-comment', ''));
                    $oldLost = $deal->getLostComment();
                    $deal->setLostComment($lostComment);
                    if ($oldLost !== $lostComment) {
                        $message = 'Deal #' . $deal->getId() . ' changed lost comment to' . PHP_EOL . $lostComment;
                        $sms = (new Sms())
                            ->setType(Sms::TYPE_DEAL_COMMENT)
                            ->setDirection(Sms::DIRECTION_NO)
                            ->setSendAt(new DateTime())
                            ->setAuthor($sysUser)
                            ->setSenderPhone($ourPhone)
                            ->setRecipientPhone($clientPhone)
                            ->setBody($message);
                        $this->containerAware->saveEntity($sms);
                    }
                }
            }

            // master
            $masterOld = $deal->getMaster();
            $masterOldId = $masterOld ? $masterOld->getId() : 0;
            if ($masterOldId !== $masterId) {
                if ($masterOld) {
                    $dealMasterOldName = TwilioConfig::getMasterNameStatic($this->containerAware, $masterOld);
                    $updateMasterIds[$masterOldId] = $masterOldId;
                } else {
                    $dealMasterOldName = '';
                }
                if ($master) {
                    $masterName = TwilioConfig::getMasterNameStatic($this->containerAware, $master);
                } else {
                    $masterName = '';
                }
                $deal->setMaster($master);
                if ($masterName) {
                    $message = 'Deal #' . $deal->getId() . ' master changed'
                        . ($dealMasterOldName ? (' from ' . $dealMasterOldName) : '')
                        . ' to ' . $masterName;
                } else {
                    $message = 'Deal #' . $deal->getId() . ' master was reset '
                        . ($dealMasterOldName ? (' from ' . $dealMasterOldName) : '');
                }
                $sms = (new Sms())
                    ->setType(Sms::TYPE_DEAL_MASTER)
                    ->setDirection(Sms::DIRECTION_NO)
                    ->setSendAt(new DateTime())
                    ->setAuthor($sysUser)
                    ->setSenderPhone($ourPhone)
                    ->setRecipientPhone($clientPhone)
                    ->setBody(trim($message));
                $this->containerAware->saveEntity($sms);
                $updateMasterDeals = true;
                if ($masterId) {
                    $updateMasterIds[$masterId] = $masterId;
                }
            }

            // amount
            $oldAmount = $deal->getAmount();
            if ($canChangeProfit && $oldAmount !== $amount) {
                $deal->setAmount($amount);
                if ($amount === null) {
                    $message = 'Deal #' . $deal->getId() . ' reset amount from $' . $oldAmount;
                } else {
                    if ($oldAmount === null) {
                        $message = 'Deal #' . $deal->getId() . ' set amount $' . $amount;
                    } else {
                        $message = 'Deal #' . $deal->getId() . ' change amount from $' . $oldAmount . ' to $' . $amount;
                    }
                }
                $sms = (new Sms())
                    ->setType(Sms::TYPE_AMOUNT)
                    ->setDirection(Sms::DIRECTION_NO)
                    ->setSendAt(new DateTime())
                    ->setAuthor($sysUser)
                    ->setSenderPhone($ourPhone)
                    ->setRecipientPhone($clientPhone)
                    ->setBody($message);
                $this->containerAware->saveEntity($sms);
            }

            // profit
            $oldProfit = $deal->getProfit();
            if ($canChangeProfit && $oldProfit !== $profit) {
                $deal->setProfit($profit);
                if ($profit === null) {
                    $message = 'Deal #' . $deal->getId() . ' reset profit from $' . $oldProfit;
                } else {
                    if ($oldProfit === null) {
                        $message = 'Deal #' . $deal->getId() . ' set profit $' . $profit;
                    } else {
                        $message = 'Deal #' . $deal->getId() . ' change profit from $' . $oldProfit . ' to $' . $profit;
                    }
                }
                $sms = (new Sms())
                    ->setType(Sms::TYPE_PROFIT)
                    ->setDirection(Sms::DIRECTION_NO)
                    ->setSendAt(new DateTime())
                    ->setAuthor($sysUser)
                    ->setSenderPhone($ourPhone)
                    ->setRecipientPhone($clientPhone)
                    ->setBody($message);
                $this->containerAware->saveEntity($sms);
            }

            // comment
            if ($deal->getComment() !== $comment) {
                $deal->setComment($comment);
                $message = 'Deal #' . $deal->getId() . ' description changed to: ' . $comment;
                $sms = (new Sms())
                    ->setType(Sms::TYPE_DEAL_COMMENT)
                    ->setDirection(Sms::DIRECTION_NO)
                    ->setSendAt(new DateTime())
                    ->setAuthor($sysUser)
                    ->setSenderPhone($ourPhone)
                    ->setRecipientPhone($clientPhone)
                    ->setBody($message);
                $this->containerAware->saveEntity($sms);
            }

            if ($deal->getAddress() !== $address) {
                $deal->setAddress($address);
                if ($address) {
                    $message = 'Deal #' . $deal->getId() . ' address changed to: ' . $address;
                } else {
                    $message = 'Deal #' . $deal->getId() . ' address removed';
                }
                $sms = (new Sms())
                    ->setType(Sms::TYPE_DEAL_COMMENT)
                    ->setDirection(Sms::DIRECTION_NO)
                    ->setSendAt(new DateTime())
                    ->setAuthor($sysUser)
                    ->setSenderPhone($ourPhone)
                    ->setRecipientPhone($clientPhone)
                    ->setBody($message);
                $this->containerAware->saveEntity($sms);
            }

            if ($isInvoiceEdit && $invoice !== $deal->getInvoice()) {
                $deal
                    ->setInvoice($invoice)
                    ->setInvoiceBy($sysUser)
                    ->setInvoiceAt(new DateTime());
            }

            if ($deal->getReviewStatus() !== $reviewStatus) {
                $reviewOld = $deal->getReviewStatus() ?: 0;
                $currentDealStatus = $deal->getStatus();
                if (!$reviewOld && !$deal->getReviewStartAt()) {
                    $deal->setReviewStartAt(new DateTime());
                }
                if (in_array($reviewStatus, DialogDeal::$reviewStatusFinal)) {
                    $deal->setReviewEndAt(new DateTime());
                    if ($currentDealStatus === DialogDeal::STATUS_FEEDBACK_RECEIVED) {
                        $deal->setStatus(DialogDeal::STATUS_FEEDBACK_PROCESSED);
                    }
                } else {
                    if ($currentDealStatus === DialogDeal::STATUS_FEEDBACK_PROCESSED) {
                        $deal->setStatus(DialogDeal::STATUS_FEEDBACK_RECEIVED);
                    };
                }
                $deal->setReviewStatus($reviewStatus);
                $this->containerAware->getMiniSmsService()->updateDealsCache(
                    $deal->getOurNumber(),
                    $deal->getClientNumber()
                );
                $this->containerAware->getMiniSmsService()->setUpdatePhoneCache($deal->getOurNumber());

                $message = 'Deal #' . $deal->getId() . ' change review status to ' . DialogDeal::$reviewStatusesName[$reviewStatus];
                $sms = (new Sms())
                    ->setType(Sms::TYPE_CHANGE_DEAL_REVIEW)
                    ->setDirection(Sms::DIRECTION_NO)
                    ->setSendAt(new DateTime())
                    ->setAuthor($sysUser)
                    ->setSenderPhone($deal->getOurNumber())
                    ->setRecipientPhone($deal->getClientNumber())
                    ->setBody($message);
                $this->containerAware->saveEntity($sms);
            }

            if ($deal->getDealRating() !== $reviewRating) {
                $changeRating = true;
                $ratingOld = $deal->getDealRating() ?: 0;
                $deal->setDealRating($reviewRating);
                $this->containerAware->getMiniSmsService()->updateDealsCache(
                    $deal->getOurNumber(),
                    $deal->getClientNumber()
                );
                $this->containerAware->getMiniSmsService()->setUpdatePhoneCache($deal->getOurNumber());

                $message = 'Deal #' . $deal->getId() . ' rating changed from ' . $ratingOld . ' to ' . $reviewRating ?: 'none';
                $sms = (new Sms())
                    ->setType(Sms::TYPE_CHANGE_DEAL_RATING)
                    ->setDirection(Sms::DIRECTION_NO)
                    ->setSendAt(new DateTime())
                    ->setAuthor($sysUser)
                    ->setSenderPhone($deal->getOurNumber())
                    ->setRecipientPhone($deal->getClientNumber())
                    ->setBody($message);
                $this->containerAware->saveEntity($sms);
            }

            // paid date
            if ($changePaidAt && (($twUser['canEditPaidDate'] ?? false) || $twUser['group'] === 'admins')) {
                $paidDateInput = trim($request->get('paid-date', ''));
                $paidTimeInput = trim($request->get('paid-time', ''));
                $paidDate = null;
                $paidDateUTC = null;
                if ($paidDateInput && $paidTimeInput) {
                    $dateTimeStr = $paidDateInput . ' ' . $paidTimeInput;
                    try {
                        $paidDate = new DateTime($dateTimeStr, $timeZone);
                        if ($paidDate) {
                            $paidDateUTC = clone $paidDate;
                            $paidDateUTC->setTimezone($timeZoneUTC);
                        } else {
                            $paidDate = null;
                        }
                    } catch (Exception $e) {
                        $paidDate = null;
                    }
                }
                $paidDateOld = $deal->getPaidAt();
                if ($paidDateOld != $paidDateUTC) {
                    $deal->setPaidAt($paidDateUTC);
                    if ($paidDate === null && $paidDateOld) {
                        $message = 'Deal #' . $deal->getId() . ' reset paid date from '
                            . $paidDateOld->setTimezone($timeZone)->format('d.m.Y H:i') . ' (' . $ourPhoneCity . ')';
                    } else {
                        if ($paidDateOld === null) {
                            $message = 'Deal #' . $deal->getId() . ' set paid date to '
                                . $paidDate->format('d.m.Y H:i') . ' (' . $ourPhoneCity . ')';
                        } else {
                            $message = 'Deal #' . $deal->getId() . ' change paid date from '
                                . $paidDateOld->setTimezone($timeZone)->format('d.m.Y H:i')
                                . ' to ' . $paidDate->format('d.m.Y H:i') . ' (' . $ourPhoneCity . ')';
                        }
                    }
                    $sms = (new Sms())
                        ->setType(Sms::TYPE_DEAL_PAID_DATE)
                        ->setDirection(Sms::DIRECTION_NO)
                        ->setSendAt(new DateTime())
                        ->setAuthor($sysUser)
                        ->setSenderPhone($ourPhone)
                        ->setRecipientPhone($clientPhone)
                        ->setBody($message);
                    $this->containerAware->saveEntity($sms);
                }
            }

            $deal->setParam($param ?: null);
            $this->containerAware->saveEntity($deal);
            if ($deal->getInvoice()) {
                $this->containerAware->getDealService()->getDealInvoiceDescriptionCached($dealId, true);
            }

        } else { // create deal
            if (in_array($status, $disabledDealStatuses, true)) {
                return $this->json(['error' => 'Forbidden status']);
            }
            $deal = (new DialogDeal())
                ->setAuthor($sysUser)
                ->setComment($comment)
                ->setAddress($address)
                ->setDealAt($dealDateUTC)
                ->setOurNumber($ourPhone)
                ->setClientNumber($clientPhone)
                ->setAmount($amount)
                ->setProfit($profit)
                ->setMaster($master)
                ->setStatus($status)
                ->setParam($param ?: null);
            if ($isInvoiceEdit && $invoice) {
                $deal
                    ->setInvoice($invoice)
                    ->setInvoiceBy($sysUser)
                    ->setInvoiceAt(new DateTime());
            }
            $dialogNewDealStatusHistory = (new DialogDealStatusHistory())
                ->setAuthor($sysUser)
                ->setDeal($deal)
                ->setStatus($status)
                ->setAmount($deal->getAmount())
                ->setProfit($deal->getProfit());
            $this->containerAware->saveEntity($deal);
            $this->containerAware->saveEntity($dialogNewDealStatusHistory);

            // make auto funnel to Deal created
            $this->containerAware->getLeadService()->leadAutoFunnel(
                DialogParams::FUNNEL_DEAL_CREATED,
                $ourPhone,
                $clientPhone,
                $sysUser
            );
        }

        if ($status === DialogDeal::STATUS_DEAL_APPROVED && !$deal->getApprovedAt()) {
            $deal
                ->setApprovedAt(new DateTime())
                ->setApprovedBy($sysUser);
            $this->containerAware->saveEntity($deal);
        }

        if ($status === DialogDeal::STATUS_IN_PROGRESS && !$deal->getProgressAt()) {
            $deal
                ->setProgressAt(new DateTime())
                ->setProgressBy($sysUser);
            $this->containerAware->saveEntity($deal);
        }

        if ($isCreate) {
            $message = 'Deal #' . $deal->getId() . ' created on date '
                . ($dealDate ? $dealDate->format('d.m.Y H:i') : 'not set') . ': '
                . $deal->getComment();
            $sms = (new Sms())
                ->setType(Sms::TYPE_DEAL_CREATE)
                ->setDirection(Sms::DIRECTION_NO)
                ->setSendAt(new DateTime())
                ->setAuthor($sysUser)
                ->setSenderPhone($ourPhone)
                ->setRecipientPhone($clientPhone)
                ->setBody($message);
            $this->containerAware->saveEntity($sms);

            [$chat, $route] = $this->containerAware->getMiniSmsService()->getPhoneUserLeadChat(
                $ourPhone,
                7428,
                TwilioConfig::TG_BOT_NEW_DEALS // Alex chat
            );
            if ($chat && $route && !$this->isDev()) {
                $ourPhoneName = TwilioConfig::getPhoneNameStatic($this->containerAware, $ourPhone);
                $clientPhoneName = TwilioConfig::getPhoneNameStatic($this->containerAware, $clientPhone);
                if (!$clientPhoneName) {
                    $clientPhoneName = 'Unknown';
                }
                $respUser = $this->containerAware->getCrmSmsRepo()->getResponsibleUser($ourPhone, $clientPhone);
                $respUserName = '';
                if ($respUser && ($respUserTw = TwilioConfig::getTwUser($this->containerAware, $respUser->getId()))) {
                    $respUserName = '#' . ($respUserTw['name'] ?? $respUser->getEmail());
                }

                $tgMessage = 'New deal on dialog ' . $ourPhoneName . ' with ' . $clientPhoneName . PHP_EOL
                    . 'City: ' . $ourPhoneCity . PHP_EOL
                    . 'Work type: ' . $ourPhoneGroup . PHP_EOL
                    . 'Amount: ' . ($amount !== null ? ('$' . $amount) : '') . PHP_EOL
                    . 'Profit: ' . ($profit !== null ? ('$' . $profit) : '') . PHP_EOL
                    . 'Responsible: ' . $respUserName . PHP_EOL
                    . 'Master: ' . ($master ? $master->getName() : '') . PHP_EOL
                    . 'Date: ' . ($dealDate ? $dealDate->format('d.m.Y H:i') : 'not set') . PHP_EOL
                    . 'Address: ' . $address . PHP_EOL
                    . 'Comment: <b>' . $comment . '</b>' . PHP_EOL
                    . 'Link to dialog: ' . $this->generateUrl($route, [
                        'from' => $ourPhone,
                        'to' => $this->containerAware->getCrmPhoneService()->getPhoneHide($clientPhone),
                    ], UrlGeneratorInterface::ABSOLUTE_URL);
                ChatService::sendTGMessage($tgMessage, TwilioConfig::TG_BOT_KEY, $chat, 'html');
            }

            // set auto responsible
            if ($ourPhoneCity
                && $ourPhoneGroup
                && ($referer = $request->headers->get('referer'))
                && ($refRoute = $this->getRouteByUrl($referer))
                && ($miniSmsConfig = TwilioConfig::getMiniSmsConfig($refRoute, []))
                && ($miniSmsConfig['autoDealRespByCity'] ?? false)
                && array_key_exists($ourPhoneGroup, $miniSmsConfig['autoDealRespByCity'])
                && $miniSmsConfig['autoDealRespByCity'][$ourPhoneGroup]
                && array_key_exists($ourPhoneCity, $miniSmsConfig['autoDealRespByCity'][$ourPhoneGroup])
                && ($respID = $miniSmsConfig['autoDealRespByCity'][$ourPhoneGroup][$ourPhoneCity])
                && ($respUser = TwilioConfig::getTwUser($this->containerAware, $respID))
                && ($respUser['active'] ?? false)
                && ($responsibleUser = $this->containerAware->getUserRepo()->getById($respID))
            ) {
                $this->containerAware->getMiniSmsService()->setLeadResponsible(
                    $ourPhone,
                    $clientPhone,
                    $responsibleUser,
                    $sysUser,
                    '#' . $respUser['name'] . ' is now auto responsible for dialog',
                    false
                );
            }
            if ($master) {
                $this->containerAware->getMiniSmsService()->getMasterOpenDeal($master->getId(), true);
            }
        }

        if (($changeStatus ?? false) && $deal->getStatus() === DialogDeal::STATUS_RESCHEDULING) {
            return $this->json(['task' => 'ok']);
        }

        $this->containerAware->getMiniSmsService()->updateDealsCache($ourPhone, $clientPhone);
        $this->containerAware->getMiniSmsService()->updateDialogAmountProfitCache($ourPhone, $clientPhone);
        $this->containerAware->getMiniSmsService()->setUpdatePhoneCache($ourPhone);
        if ($updateMasterDeals && $updateMasterIds) {
            foreach ($updateMasterIds as $id) {
                $this->containerAware->getMiniSmsService()->getMasterOpenDeal($id, true);
            }
        }
        //update master rating
        if ($changeRating) {
            $dealMaster = $deal->getMaster();
            if ($dealMaster) {
                $masterRating = $this->containerAware->getDialogDealRepo()->getAverageRatingByMaster($dealMaster->getId());
                $dealMaster->setAverageRating($masterRating);
                $this->containerAware->saveEntity($dealMaster);
            }
        }

        return $this->json(['success' => $updateMasterIds]);
    }

    /**
     * @Route("/sms-mini/deal/status", name="twilio_sms_deal_status")
     *
     * @param Request $request
     * @return Response
     */
    public function smsMiniDialogStatusAction(Request $request): Response
    {
        /** @var User $sysUser */
        try {
            [$sysUser, $twUser] = $this->checkRight($request, true);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
        }

        $id = (int)preg_replace('@\D@', '', $request->get('id', ''));
        $status = (int)preg_replace('@\D@', '', $request->get('status', ''));

        if (!$id || !($deal = $this->containerAware->getDialogDealRepo()->getById($id))) {
            return $this->json(['error' => 'Deal not found. Refresh page']);
        }

        $statusesList = $this->containerAware->getMiniSmsService()->getDealStatuses($deal->getOurNumber());
        if (!array_key_exists($status, $statusesList)) {
            return $this->json(['error' => 'Incorrect status']);
        }
        $disabledDealStatuses = $this->containerAware->getDealService()->getDisabledDealStatuses($twUser, $deal);

        $changeStatus = false;
        $updateMasterDeals = in_array($deal->getStatus(), DialogDeal::$statusesClose, true) !== in_array($status,
                DialogDeal::$statusesClose, true);
        if ($deal->getStatus() !== $status) {
            if (in_array($status, $disabledDealStatuses, true)) {
                return $this->json(['error' => 'Forbidden status']);
            }
            $changeStatus = true;
            $ourPhone = $deal->getOurNumber();
            $clientPhone = $deal->getClientNumber();
            $statusNameOld = $deal->getStatusName();
            $ourPhoneGroup = $this->containerAware->getMiniSmsService()->getGroupDialog($ourPhone, $clientPhone);

            if (!TwilioConfig::isAccessGroup($twUser, $ourPhoneGroup)) {
                return $this->json(['error' => 'Deal not found. Refresh page']);
            }

            if (!$deal->getPaidAt()
                && in_array($status, DialogDeal::$successfullyCompletedDeal)
            ) {
                $deal->setPaidAt(new DateTime());
            }
            if (($status === DialogDeal::STATUS_INVOICE || $status === DialogDeal::STATUS_GROUP_INVOICE)
                && !$deal->getInvoiceStatusAt()
            ) {
                $deal->setInvoiceStatusAt(new DateTime());
                $changePaidAt = false;
            }
            if ($status === DialogDeal::STATUS_DEAL_APPROVED) {
                $respUser = $this->containerAware->getCrmSmsRepo()->getResponsibleUser($ourPhone, $clientPhone);
                $author = $respUser ?: $sysUser;
                $this->containerAware->getMiniSmsService()->createNewTask(
                    $ourPhone,
                    $clientPhone,
                    $author,
                    $author,
                    'Notify the master about the deal #' . $deal->getId(),
                    new DateTime(),
                    $this->isDev()
                );
            } elseif ($status === DialogDeal::STATUS_APPROVED_BY_INSTALLER) {
                $master = null;
                $ourPhoneCity = $this->containerAware->getMiniSmsService()->getCityDialog($ourPhone, $clientPhone);
                $masters = $this->containerAware->getMiniSmsService()->getMasterList(
                    $ourPhoneCity,
                    $ourPhoneGroup
                );
                if ($masterId = (int)$request->get('masterId', 0)) {
                    if (!array_key_exists($masterId, $masters)) {
                        return $this->getErrorRequest($request->isXmlHttpRequest(), 'Incorrect master');
                    }
                    $master = $this->containerAware->getMasterRepo()->getById($masterId);
                    if (!$master) {
                        return $this->getErrorRequest($request->isXmlHttpRequest(), 'Master not found');
                    }
                }

                $masterOld = $deal->getMaster();
                $masterOldId = $masterOld ? $masterOld->getId() : 0;
                if ($masterOldId !== $masterId) {
                    if ($masterOld) {
                        $dealMasterOldName = TwilioConfig::getMasterNameStatic($this->containerAware, $masterOld);
                    } else {
                        $dealMasterOldName = '';
                    }
                    if ($master) {
                        $masterName = TwilioConfig::getMasterNameStatic($this->containerAware, $master);
                    } else {
                        $masterName = '';
                    }
                    $deal->setMaster($master);
                    if ($masterName) {
                        $message = 'Deal #' . $deal->getId() . ' master changed'
                            . ($dealMasterOldName ? (' from ' . $dealMasterOldName) : '')
                            . ' to ' . $masterName;
                    } else {
                        $message = 'Deal #' . $deal->getId() . ' master was reset '
                            . ($dealMasterOldName ? (' from ' . $dealMasterOldName) : '');
                    }
                    $sms = (new Sms())
                        ->setType(Sms::TYPE_DEAL_MASTER)
                        ->setDirection(Sms::DIRECTION_NO)
                        ->setSendAt(new DateTime())
                        ->setAuthor($sysUser)
                        ->setSenderPhone($ourPhone)
                        ->setRecipientPhone($clientPhone)
                        ->setBody(trim($message));
                    $this->containerAware->saveEntity($sms);
                }

            } elseif (in_array($status, DialogDeal::$statusesLost, true)) {
                $lostComment = trim($request->get('lost-comment', ''));
                $deal->setLostComment($lostComment);
            }
            $deal->setStatus($status);
            if ($status === DialogDeal::STATUS_DEAL_APPROVED && !$deal->getApprovedAt()) {
                $deal
                    ->setApprovedAt(new DateTime())
                    ->setApprovedBy($sysUser);
            }

            if ($status === DialogDeal::STATUS_IN_PROGRESS && !$deal->getProgressAt()) {
                $deal
                    ->setProgressAt(new DateTime())
                    ->setProgressBy($sysUser);
                $this->containerAware->saveEntity($deal);
            }

            $this->containerAware->saveEntity($deal);
            $this->containerAware->getMiniSmsService()->updateDealsCache($deal->getOurNumber(),
                $deal->getClientNumber());
            $this->containerAware->getMiniSmsService()->setUpdatePhoneCache($deal->getOurNumber());
            $statusName = $deal->getStatusName();

            $message = 'Deal #' . $deal->getId() . ' status changed from ' . $statusNameOld . ' to ' . $statusName;
            $sms = (new Sms())
                ->setType(Sms::TYPE_DEAL_STATUS)
                ->setDirection(Sms::DIRECTION_NO)
                ->setSendAt(new DateTime())
                ->setAuthor($sysUser)
                ->setSenderPhone($deal->getOurNumber())
                ->setRecipientPhone($deal->getClientNumber())
                ->setBody($message);
            $this->containerAware->saveEntity($sms);
            $dialogDealStatusHistory = (new DialogDealStatusHistory())
                ->setAuthor($sysUser)
                ->setDeal($deal)
                ->setStatus($status)
                ->setAmount($deal->getAmount())
                ->setProfit($deal->getProfit());
            $this->containerAware->saveEntity($dialogDealStatusHistory);
        }
        if ($updateMasterDeals && ($master = $deal->getMaster())) {
            $this->containerAware->getMiniSmsService()->getMasterOpenDeal($master->getId(), true);
        }
        if ($changeStatus && $deal->getStatus() === DialogDeal::STATUS_RESCHEDULING) {
            return $this->json(['task' => 'ok']);
        }

        return $this->json(['success' => 'ok']);
    }

    /**
     * @Route("/sms-mini/deal/rating", name="twilio_sms_deal_rating")
     *
     * @param Request $request
     * @return Response
     */
    public function smsMiniDialogRatingAction(Request $request): Response
    {
        /** @var User $sysUser */
        try {
            [$sysUser, $twUser] = $this->checkRight($request, true);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
        }

        $id = (int)preg_replace('@\D@', '', $request->get('id', ''));
        $rating = (int)preg_replace('@\D@', '', $request->get('rating', ''));

        if (!$id || !($deal = $this->containerAware->getDialogDealRepo()->getById($id))) {
            return $this->json(['error' => 'Deal not found. Refresh page']);
        }

        if ($deal->getDealRating() !== $rating) {
            $ratingOld = $deal->getDealRating() ?: 0;
            $deal->setDealRating($rating);
            $this->containerAware->saveEntity($deal);
            $this->containerAware->getMiniSmsService()->updateDealsCache($deal->getOurNumber(), $deal->getClientNumber());
            $this->containerAware->getMiniSmsService()->setUpdatePhoneCache($deal->getOurNumber());

            $message = 'Deal #' . $deal->getId() . ' rating changed from ' . $ratingOld  . ' to ' . $rating ?: 'none';
            $sms = (new Sms())
                ->setType(Sms::TYPE_CHANGE_DEAL_RATING)
                ->setDirection(Sms::DIRECTION_NO)
                ->setSendAt(new DateTime())
                ->setAuthor($sysUser)
                ->setSenderPhone($deal->getOurNumber())
                ->setRecipientPhone($deal->getClientNumber())
                ->setBody($message);
            $this->containerAware->saveEntity($sms);
            $dealMaster = $deal->getMaster();
            if ($dealMaster) {
                $masterRating = $this->containerAware->getDialogDealRepo()->getAverageRatingByMaster($dealMaster->getId());
                $dealMaster->setAverageRating($masterRating);
                $this->containerAware->saveEntity($dealMaster);
            }
        }

        return $this->json(['success' => 'ok']);
    }

    /**
     * @Route("/sms-mini/deal/review", name="twilio_sms_deal_review")
     *
     * @param Request $request
     * @return Response
     */
    public function smsMiniDialogReviewAction(Request $request): Response
    {
        /** @var User $sysUser */
        try {
            [$sysUser, $twUser] = $this->checkRight($request, true);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
        }

        $id = (int)preg_replace('@\D@', '', $request->get('id', ''));
        $review = (int)preg_replace('@\D@', '', $request->get('review', ''));

        if (!$id || !($deal = $this->containerAware->getDialogDealRepo()->getById($id))) {
            return $this->json(['error' => 'Deal not found. Refresh page']);
        }

        if (!array_key_exists($review, DialogDeal::$reviewStatusesName)) {
            return $this->json(['error' => 'Incorrect review status']);
        }

        if ($deal->getReviewStatus() !== $review) {
            $reviewOld = $deal->getReviewStatus() ?: 0;
            $currentDealStatus = $deal->getStatus();
            if (!$reviewOld && !$deal->getReviewStartAt()) {
                $deal->setReviewStartAt(new DateTime());
            }
//            if (in_array($review, DialogDeal::$reviewStatusFinal) && !$deal->getReviewEndAt()) {
            if (in_array($review, DialogDeal::$reviewStatusFinal)) {
                $deal->setReviewEndAt(new DateTime());
                if ($currentDealStatus === DialogDeal::STATUS_FEEDBACK_RECEIVED) {
                    $deal->setStatus(DialogDeal::STATUS_FEEDBACK_PROCESSED);
                };
            } else {
                if ($currentDealStatus === DialogDeal::STATUS_FEEDBACK_PROCESSED) {
                    $deal->setStatus(DialogDeal::STATUS_FEEDBACK_RECEIVED);
                };
            }
            $deal->setReviewStatus($review);
            $this->containerAware->saveEntity($deal);
            $this->containerAware->getMiniSmsService()->updateDealsCache($deal->getOurNumber(), $deal->getClientNumber());
            $this->containerAware->getMiniSmsService()->setUpdatePhoneCache($deal->getOurNumber());

            $message = 'Deal #' . $deal->getId() . ' change review status to ' . DialogDeal::$reviewStatusesName[$review] ;
            $sms = (new Sms())
                ->setType(Sms::TYPE_CHANGE_DEAL_REVIEW)
                ->setDirection(Sms::DIRECTION_NO)
                ->setSendAt(new DateTime())
                ->setAuthor($sysUser)
                ->setSenderPhone($deal->getOurNumber())
                ->setRecipientPhone($deal->getClientNumber())
                ->setBody($message);
            $this->containerAware->saveEntity($sms);
        }

        return $this->json(['success' => 'ok']);
    }

    /**
     * @Route("/sms-mini/deal/change-date", name="twilio_sms_deal_change_date")
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function smsMiniDealChangeDateAction(Request $request): Response
    {
        /** @var User $sysUser */
        try {
            [$sysUser, $twUser] = $this->checkRight($request, true);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
        }

        $id = (int)preg_replace('@\D@', '', $request->get('id', ''));
        $dateInput = trim($request->get('date', ''));
        $timeInput = trim($request->get('time', ''));

        if (!$id || !($deal = $this->containerAware->getDialogDealRepo()->getById($id))) {
            return $this->json(['error' => 'Deal not found. Refresh page']);
        }
        $ourPhone = $deal->getOurNumber();
        $clientPhone = $deal->getClientNumber();
        $ourPhoneGroup = $this->containerAware->getMiniSmsService()->getGroupDialog($ourPhone, $clientPhone);
        $ourPhoneCity = $this->containerAware->getMiniSmsService()->getCityDialog($ourPhone, $clientPhone);

        if (!TwilioConfig::isAccessGroup($twUser, $ourPhoneGroup)) {
            return $this->json(['error' => 'Deal not found. Refresh page']);
        }

        $prevDeal = clone $deal;
        $timeZone = new DateTimeZone(
            TwilioConfig::CITY_PHONE_CODES[$ourPhoneCity]['timeZone'] ?? TwilioConfig::DEFAULT_TIME_ZONE
        );
        $timeZoneUTC = new DateTimeZone(date_default_timezone_get());
        $dealDate = null;
        $dealDateUTC = null;
        if ($dateInput && $timeInput) {
            $dateTimeStr = $dateInput . ' ' . $timeInput;
            try {
                $dealDate = new DateTime($dateTimeStr, $timeZone);
                if ($dealDate) {
                    $dealDateUTC = clone $dealDate;
                    $dealDateUTC->setTimezone($timeZoneUTC);
                } else {
                    $dealDate = null;
                }
            } catch (Exception $e) {
                $dealDate = null;
            }
        }
        if (!$dealDate) {
            return $this->json(['error' => 'Incorrect deal date']);
        }

        $dealDateOld = $deal->getDealAt();
        if ($dealDateOld != $dealDateUTC) {
            $deal->setDealAt($dealDateUTC);
            $this->containerAware->saveEntity($deal);

            $message = 'Deal #' . $deal->getId() . ' date changed'
                . ($dealDateOld ? (' from ' . $dealDateOld->setTimezone($timeZone)->format('d.m.Y H:i T')) : '')
                . ' to ' . $dealDate->setTimezone($timeZone)->format('d.m.Y H:i T');
            $sms = (new Sms())
                ->setType(Sms::TYPE_DEAL_DATE)
                ->setDirection(Sms::DIRECTION_NO)
                ->setSendAt(new DateTime())
                ->setAuthor($sysUser)
                ->setSenderPhone($ourPhone)
                ->setRecipientPhone($clientPhone)
                ->setBody($message);
            $this->containerAware->saveEntity($sms);
            $this->containerAware->getEntityChangesService()->checkObjectDifference(
                $sysUser,
                $request,
                $prevDeal,
                $deal
            );
        }

        return $this->json(['success' => 'ok']);
    }

    /**
     * @Route("/sms-mini/deal/delete", name="twilio_sms_deal_delete")
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function smsMiniDealDeleteAction(Request $request): Response
    {
        /** @var User $sysUser */
        try {
            [$sysUser, $twUser] = $this->checkRight($request, true);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
        }
        if (!($twUser['canDeleteDeals'] ?? false) && $twUser['group'] !== 'admins') {
            return $this->getErrorRequest($request->isXmlHttpRequest(), 'You do not have permission ');
        }
        $deal = null;
        $ourPhone = '';
        $clientPhone = '';
        if ($dealId = (int)$request->get('dealId', 0)) {
            $deal = $this->containerAware->getDialogDealRepo()->getById($dealId);
            if (!$deal) {
                return $this->getErrorRequest($request->isXmlHttpRequest(), 'Deal not found');
            }
            $ourPhone = $deal->getOurNumber();
            $clientPhone = $deal->getClientNumber();
        }

        if ($ourPhone && $clientPhone) {
            $this->containerAware->removeEntity($deal);
            $this->containerAware->getMiniSmsService()->updateDealsCache($ourPhone, $clientPhone);
            $this->containerAware->getMiniSmsService()->updateDialogAmountProfitCache($ourPhone, $clientPhone);
            $this->containerAware->getMiniSmsService()->setUpdatePhoneCache($ourPhone);
            $message = 'Deal #' . $dealId . ' deleted ';
            $sms = (new Sms())
                ->setType(Sms::TYPE_DEAL_DELETE)
                ->setDirection(Sms::DIRECTION_NO)
                ->setSendAt(new DateTime())
                ->setAuthor($sysUser)
                ->setSenderPhone($ourPhone)
                ->setRecipientPhone($clientPhone)
                ->setBody($message);
            $this->containerAware->saveEntity($sms);
            $this->containerAware->getMiniSmsService()->getDealCount($ourPhone, $clientPhone, true);
        }

        return $this->json(['success' => 'Deal deleted']);
    }

    /**
     * @Route("/sms-mini/deal/form/date", name="twilio_crm_deal_form_date")
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function dialogDealFormDate(Request $request): Response
    {
        /** @var User $sysUser */
        try {
            [$sysUser, $twUser] = $this->checkRight($request, true);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), $e->getMessage());
        }
        $dealId = (int)$request->get('id', 0);
        $deal = $this->containerAware->getDialogDealRepo()->getById($dealId);
        if (!$deal) {
            return $this->getErrorRequest($request->isXmlHttpRequest(), 'Deal not found');
        }
        $ourPhone = $this->containerAware->getCrmPhoneService()->getPhoneReal($request->get('phone1', ''));
        $clientPhone = $this->containerAware->getCrmPhoneService()->getPhoneReal($request->get('phone2', ''));
        if ($ourPhone && $clientPhone) {
            $ourPhoneCity = $this->containerAware->getMiniSmsService()->getCityDialog($ourPhone, $clientPhone);
            $timeZone = new DateTimeZone(TwilioConfig::CITY_PHONE_CODES[$ourPhoneCity]['timeZone']
                ?? TwilioConfig::DEFAULT_TIME_ZONE);
            $timeZoneUTC = new DateTimeZone(date_default_timezone_get());
            $dateInput = trim($request->get('date', ''));
            $timeInput = trim($request->get('time', ''));
            $date = null;
            $dateUTC = null;
            if ($dateInput && $timeInput) {
                $dateTimeStr = $dateInput . ' ' . $timeInput;
                try {
                    $date = new DateTime($dateTimeStr, $timeZone);
                    if ($date) {
                        $dateUTC = clone $date;
                        $dateUTC->setTimezone($timeZoneUTC);
                    } else {
                        $date = null;
                    }
                } catch (Exception $e) {
                    $date = null;
                }
            }
            if (!$date) {
                return $this->json(['error' => 'Incorrect date']);
            }
            $respUser = $this->containerAware->getCrmSmsRepo()->getResponsibleUser($ourPhone, $clientPhone);
            $author = $respUser ?: $sysUser;
            $this->containerAware->getMiniSmsService()->createNewTask(
                $ourPhone,
                $clientPhone,
                $sysUser,
                $author,
                'Discuss with client a new date of the deal #' . $dealId,
                $dateUTC,
                $this->isDev()
            );
            return $this->json(['success' => 'Task created']);
        }

        $ourPhone = $deal->getOurNumber();
        $clientPhone = $deal->getClientNumber();
        $ourPhoneCity = $this->containerAware->getMiniSmsService()->getCityDialog($ourPhone, $clientPhone);
        $timeZone = new DateTimeZone(
            TwilioConfig::CITY_PHONE_CODES[$ourPhoneCity]['timeZone'] ?? TwilioConfig::DEFAULT_TIME_ZONE
        );

        $json = [
            'html' => $this->renderView('AppBundle:Front:Twilio/deals/deal-date-form.html.twig', [
                'deal' => $deal,
                'ourPhone' => $ourPhone,
                'clientPhone' => $clientPhone,
                'timeZone' => $timeZone,
                'city' => $ourPhoneCity,

            ])
        ];
        return $this->json($json);
    }

}
