<?php

declare(strict_types=1);

namespace AppBundle\Entity\CRM;

use AppBundle\Entity\User\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="crm_sms_dialog_deal", indexes={
 *     @ORM\Index(name="createdAt", columns={"created_at"}),
 *     @ORM\Index(name="dealAt", columns={"deal_at"}),
 *     @ORM\Index(name="ournumber_clientnumber", columns={"our_number", "client_number"}),
 *     @ORM\Index(name="status", columns={"status"})
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CRM\DialogDealRepository")
 */
class DialogDeal
{
    public const STATUS_NONE = 0;
//    public const STATUS_ESTIMATE = 1;
    public const STATUS_DEAL_CREATED = 2;
    public const STATUS_APPROVED_BY_INSTALLER = 3;
    public const STATUS_IN_PROGRESS = 4;
    public const STATUS_DONE = 5;
    public const STATUS_INVOICE = 6;
    public const STATUS_PAID = 7;
    public const STATUS_LOST = 8;
    public const STATUS_RESCHEDULING = 9;
    public const STATUS_FEEDBACK_RECEIVED = 10;
    public const STATUS_LOST_SALES = 11;
    public const STATUS_LOST_MASTER = 12;
    public const STATUS_LOST_CUSTOMER = 13;
    public const STATUS_FEEDBACK_PROCESSED = 14;
    public const STATUS_LOST_SALES_NEW = 15;
    public const STATUS_LOST_NO_INSTALLER = 16;
    public const STATUS_LOST_DISPATCHER = 17;
    public const STATUS_CONFIRMED = 18;
    public const STATUS_DEAL_APPROVED = 19;
    public const STATUS_NO_INSTALLER = 20;
    public const STATUS_DEAL_CORRECTION = 21;
    public const STATUS_SURVEY_IN_PROGRESS = 22;
    public const STATUS_SURVEY_DONE = 23;
    public const STATUS_LOST_NOT_APPROVED = 24;
    public const STATUS_GROUP_INVOICE = 25;

    public static $statusNameShort = [
        self::STATUS_DEAL_CREATED => 'Create',
        self::STATUS_DEAL_CORRECTION => 'Correction',
        self::STATUS_DEAL_APPROVED => 'Approved',
        self::STATUS_NO_INSTALLER => 'No installer',
        self::STATUS_APPROVED_BY_INSTALLER => 'Accept',
        self::STATUS_RESCHEDULING => 'Rescheduling',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_IN_PROGRESS => 'Progr',
        self::STATUS_DONE => 'Done',
        self::STATUS_INVOICE => 'Inv',
        self::STATUS_GROUP_INVOICE => 'InvGr',
        self::STATUS_PAID => 'Paid',
        self::STATUS_FEEDBACK_RECEIVED => 'Feedback',
        self::STATUS_FEEDBACK_PROCESSED => 'Feedback proc',
        self::STATUS_SURVEY_IN_PROGRESS => 'Survey in progr',
        self::STATUS_SURVEY_DONE => 'Survey done',
        self::STATUS_LOST_SALES_NEW => 'Lost:Sales',
        self::STATUS_LOST_MASTER => 'Lost: Master',
        self::STATUS_LOST_CUSTOMER => 'Lost:Custom',
        self::STATUS_LOST_NO_INSTALLER => 'Lost:NoInst',
        self::STATUS_LOST_DISPATCHER => 'Lost:Disp',
        self::STATUS_LOST_NOT_APPROVED => 'Lost:NotApprv',
//        self::STATUS_NONE => 'None',
    ];
    public static $statusName = [
        self::STATUS_DEAL_CREATED => 'Deal created',
        self::STATUS_DEAL_CORRECTION => 'Deal correction',
        self::STATUS_DEAL_APPROVED => 'Deal approved',
        self::STATUS_NO_INSTALLER => 'No installer',
        self::STATUS_APPROVED_BY_INSTALLER => 'Accepted by installer',
        self::STATUS_RESCHEDULING => 'Rescheduling',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_IN_PROGRESS => 'In progress',
        self::STATUS_DONE => 'Done',
        self::STATUS_INVOICE => 'Invoice',
        self::STATUS_GROUP_INVOICE => 'Group Invoice',
        self::STATUS_PAID => 'Paid',
        self::STATUS_FEEDBACK_RECEIVED => 'Ready for feedback',
        self::STATUS_FEEDBACK_PROCESSED => 'Feedback processed',
        self::STATUS_SURVEY_IN_PROGRESS => 'Survey in progress',
        self::STATUS_SURVEY_DONE => 'Survey done',
        self::STATUS_LOST_SALES_NEW => 'Lost: Sales',
        self::STATUS_LOST_MASTER => 'Lost: Master',
        self::STATUS_LOST_CUSTOMER => 'Lost: Customer',
        self::STATUS_LOST_NO_INSTALLER => 'Lost: no Installer',
        self::STATUS_LOST_DISPATCHER => 'Lost: Dispatcher',
        self::STATUS_LOST_NOT_APPROVED => 'Lost: Not approved',
//        self::STATUS_NONE => 'None',
    ];

    public const STATUS_DROP_DEAL_NEW = 101;
    public const STATUS_DROP_SLAB_ON_SPOT = 102;
    public const STATUS_DROP_EPOXY_POURED = 103;
    public const STATUS_DROP_DELIVERY_READY = 104;
    public const STATUS_DROP_DELIVERED = 105;
    public const STATUS_DROP_CUSTOMER_PAID = 106;
    public const STATUS_DROP_COMMISSION_PAID = 107;
    public const STATUS_DROP_REVIEW_RECEIVED = 108;
    public const STATUS_DROP_PROJECT_COMPLETED = 109;
    public const STATUS_DROP_ANY = 110;
    public const STATUS_DEAL_LOST_ANY = 111;
    public static $statusNameShortDrop = [
        self::STATUS_NONE => 'None',
        self::STATUS_DROP_DEAL_NEW => 'DealNew',
        self::STATUS_DROP_SLAB_ON_SPOT => 'Slab',
        self::STATUS_DROP_EPOXY_POURED => 'Poured',
        self::STATUS_DROP_DELIVERY_READY => 'Ready',
        self::STATUS_DROP_DELIVERED => 'Delivered',
        self::STATUS_DROP_CUSTOMER_PAID => 'CustPaid',
        self::STATUS_DROP_COMMISSION_PAID => 'Paid',
        self::STATUS_DROP_REVIEW_RECEIVED => 'Review',
        self::STATUS_DROP_PROJECT_COMPLETED => 'Done',
//        self::STATUS_DROP_ANY => 'Drop deals',
    ];

    public static $statusNameDrop = [
//        self::STATUS_NONE => 'None',
        self::STATUS_DROP_DEAL_NEW => 'New Deal',
        self::STATUS_DROP_SLAB_ON_SPOT => 'Slab on spot',
        self::STATUS_DROP_EPOXY_POURED => 'Epoxy poured',
        self::STATUS_DROP_DELIVERY_READY => 'Delivery ready',
        self::STATUS_DROP_DELIVERED => 'Delivered',
        self::STATUS_DROP_CUSTOMER_PAID => 'Customer paid',
        self::STATUS_DROP_COMMISSION_PAID => 'Commission paid',
        self::STATUS_DROP_REVIEW_RECEIVED => 'Review received',
        self::STATUS_DROP_PROJECT_COMPLETED => 'Project completed',
    ];
    public static $statusesClose = [
        self::STATUS_DROP_PROJECT_COMPLETED,
        self::STATUS_PAID,
        self::STATUS_LOST,
        self::STATUS_FEEDBACK_PROCESSED,
        self::STATUS_FEEDBACK_RECEIVED,
        self::STATUS_SURVEY_IN_PROGRESS,
        self::STATUS_SURVEY_DONE,
        self::STATUS_LOST_SALES,
        self::STATUS_LOST_SALES_NEW,
        self::STATUS_LOST_MASTER,
        self::STATUS_LOST_CUSTOMER,
        self::STATUS_LOST_NO_INSTALLER,
        self::STATUS_LOST_DISPATCHER,
        self::STATUS_LOST_NOT_APPROVED,
    ];
    public static $successfullyCompletedDeal = [
        self::STATUS_DROP_PROJECT_COMPLETED,
        self::STATUS_PAID,
        self::STATUS_FEEDBACK_PROCESSED,
        self::STATUS_FEEDBACK_RECEIVED,
        self::STATUS_SURVEY_IN_PROGRESS,
        self::STATUS_SURVEY_DONE,
    ];
    public static $statusesLost = [
        self::STATUS_LOST,
        self::STATUS_LOST_SALES,
        self::STATUS_LOST_SALES_NEW,
        self::STATUS_LOST_MASTER,
        self::STATUS_LOST_CUSTOMER,
        self::STATUS_LOST_NO_INSTALLER,
        self::STATUS_LOST_DISPATCHER,
        self::STATUS_LOST_NOT_APPROVED,
    ];
    public static $unusedDealStatuses = [
        self::STATUS_LOST_SALES => 'Lost:Sales',
        self::STATUS_LOST => 'Lost',
        self::STATUS_NONE => 'None',
    ];
    public static $disabledDealStatuses = [
        self::STATUS_NO_INSTALLER,
        self::STATUS_APPROVED_BY_INSTALLER,
        self::STATUS_RESCHEDULING,
        self::STATUS_CONFIRMED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_DONE,
        self::STATUS_INVOICE,
        self::STATUS_GROUP_INVOICE,
        self::STATUS_PAID,
        self::STATUS_FEEDBACK_RECEIVED,
        self::STATUS_FEEDBACK_PROCESSED,
        self::STATUS_SURVEY_IN_PROGRESS,
        self::STATUS_SURVEY_DONE,
        self::STATUS_LOST_SALES_NEW,
        self::STATUS_LOST_MASTER ,
        self::STATUS_LOST_CUSTOMER,
        self::STATUS_LOST_NO_INSTALLER,
        self::STATUS_LOST_DISPATCHER,
        self::STATUS_LOST_NOT_APPROVED,
    ];
    public static $disabledDealStatusesNoInvoice = [
        self::STATUS_PAID,
        self::STATUS_FEEDBACK_RECEIVED,
        self::STATUS_FEEDBACK_PROCESSED,
        self::STATUS_SURVEY_IN_PROGRESS,
        self::STATUS_SURVEY_DONE,
        self::STATUS_LOST_NOT_APPROVED
//        self::STATUS_LOST_SALES_NEW,
//        self::STATUS_LOST_MASTER ,
//        self::STATUS_LOST_CUSTOMER,
//        self::STATUS_LOST_NO_INSTALLER,
//        self::STATUS_LOST_DISPATCHER,
    ];
    public static $startDealStatuses = [
        self::STATUS_NONE,
        self::STATUS_DEAL_CREATED,
        self::STATUS_DEAL_CORRECTION,
    ];
    public static $canChangeProfitStatuses = [
        self::STATUS_DEAL_CREATED,
        self::STATUS_DEAL_CORRECTION,
        self::STATUS_DEAL_APPROVED,
        self::STATUS_NO_INSTALLER,
        self::STATUS_APPROVED_BY_INSTALLER,
        self::STATUS_RESCHEDULING,
        self::STATUS_CONFIRMED,
    ];
    public const REVIEW_STATUS_NONE = 0;
    public const REVIEW_STATUS_ASKED_RATE = 1;
    public const REVIEW_STATUS_ASKED_REVIEW = 2;
    public const REVIEW_STATUS_GOT_REVIEW = 3;
    public const REVIEW_STATUS_WITHOUT_FEEDBACK = 4;
    public const REVIEW_STATUS_NO_ANSWER = 5;
    public const REVIEW_STATUS_BAD_WORK = 6;
    public const REVIEW_STATUS_WITHOUT_SURVEY_AND_FEEDBACK = 7;

    // review statuses
    public static $reviewStatuses = [
        self::REVIEW_STATUS_NONE,
        self::REVIEW_STATUS_ASKED_RATE,
        self::REVIEW_STATUS_ASKED_REVIEW,
        self::REVIEW_STATUS_GOT_REVIEW,
        self::REVIEW_STATUS_WITHOUT_FEEDBACK,
        self::REVIEW_STATUS_WITHOUT_SURVEY_AND_FEEDBACK,
        self::REVIEW_STATUS_NO_ANSWER,
        self::REVIEW_STATUS_BAD_WORK,
    ];
    public static $reviewStatusFinal = [
        self::REVIEW_STATUS_GOT_REVIEW,
        self::REVIEW_STATUS_WITHOUT_FEEDBACK,
        self::REVIEW_STATUS_WITHOUT_SURVEY_AND_FEEDBACK,
        self::REVIEW_STATUS_NO_ANSWER,
        self::REVIEW_STATUS_BAD_WORK,
    ];
    public static $reviewStatusesName = [
        self::REVIEW_STATUS_ASKED_RATE => 'Asked to rate',
        self::REVIEW_STATUS_ASKED_REVIEW => 'Asked review on yelp',
        self::REVIEW_STATUS_GOT_REVIEW => 'Got review on yelp',
        self::REVIEW_STATUS_WITHOUT_FEEDBACK => 'Without feedback on yelp',
        self::REVIEW_STATUS_WITHOUT_SURVEY_AND_FEEDBACK => 'Without survey and feedback on Yelp',
        self::REVIEW_STATUS_NO_ANSWER => 'No answer',
        self::REVIEW_STATUS_BAD_WORK => 'Bad work',
        self::REVIEW_STATUS_NONE => 'Not set',
    ];
    public static $reviewStatusesNameShort = [
        self::REVIEW_STATUS_ASKED_RATE => 'Asked',
        self::REVIEW_STATUS_ASKED_REVIEW => 'Asked yelp',
        self::REVIEW_STATUS_GOT_REVIEW => 'Got yelp',
        self::REVIEW_STATUS_WITHOUT_FEEDBACK => 'Without yelp',
        self::REVIEW_STATUS_WITHOUT_SURVEY_AND_FEEDBACK => 'Without survey',
        self::REVIEW_STATUS_NO_ANSWER => 'No answer',
        self::REVIEW_STATUS_BAD_WORK => 'Bad work',
        self::REVIEW_STATUS_NONE => 'None',
    ];
    public static $masterSummaryStatus = [
        DialogDeal::STATUS_DEAL_CREATED,
        DialogDeal::STATUS_DEAL_CORRECTION,
        DialogDeal::STATUS_DEAL_APPROVED,
        DialogDeal::STATUS_NO_INSTALLER
    ];

    // deal statuses css colors
    public static $statusColors = [
        self::STATUS_DEAL_CREATED => [
            'base' => [
                'background-color' => '#deded7',
                'border-color' => '#deded7',
                'color' => ' #000',
            ],
        ],
        self::STATUS_DEAL_CORRECTION=> [
            'base' => [
                'background-color' => '#969595',
                'border-color' => '#969595',
                'color' => ' #fff',
            ],
        ],
        self::STATUS_DEAL_APPROVED => [
            'base' => [
                'background-color' => '#f3f69e',
                'border-color' => '#f3f69e',
                'color' => ' #000',
            ]
        ],
        self::STATUS_NO_INSTALLER => [
            'base' => [
                'background-color' => '#f57878',
                'border-color' => '#f57878',
                'color' => ' #000',
            ],
        ],
        self::STATUS_APPROVED_BY_INSTALLER => [
            'base' => [
                'background-color' => '#e1ff1e',
                'border-color' => '#e1ff1e',
                'color' => ' #000',
            ],
        ],
        self::STATUS_RESCHEDULING => [
            'base' => [
                'background-color' => '#e38858',
                'border-color' => '#e38858',
                'color' => ' #000',
            ],
        ],
        self::STATUS_CONFIRMED => [
            'base' => [
                'background-color' => '#ccf4fc',
                'border-color' => '#ccf4fc',
                'color' => ' #000',
            ],
        ],
        self::STATUS_IN_PROGRESS => [
            'base' => [
                'background-color' => '#42bae7',
                'border-color' => '#42bae7',
                'color' => ' #000',
            ],
        ],
        self::STATUS_DONE =>  [
            'base' => [
                'background-color' => '#3998c2',
                'border-color' => '#3998c2',
                'color' => ' #fff',
            ],
        ],
        self::STATUS_INVOICE => [
            'base' => [
                'background-color' => '#0c6388',
                'border-color' => '#0c6388',
                'color' => ' #fff',
            ],
        ],
        self::STATUS_GROUP_INVOICE => [
            'base' => [
                'background-color' => '#0c6388',
                'border-color' => '#0c6388',
                'color' => ' #fff',
            ],
        ],
        self::STATUS_PAID => [
            'base' => [
                'background-color' => '#9af5a6',
                'border-color' => '#9af5a6',
                'color' => ' #000',
            ],
        ],
        self::STATUS_FEEDBACK_RECEIVED =>  [
            'base' => [
                'background-color' => '#0ce710',
                'border-color' => '#0ce710',
                'color' => ' #fff',
            ],
        ],
        self::STATUS_FEEDBACK_PROCESSED =>  [
            'base' => [
                'background-color' => '#077c35',
                'border-color' => '#077c35',
                'color' => ' #fff',
            ],
        ],
        self::STATUS_SURVEY_IN_PROGRESS =>  [
            'base' => [
                'background-color' => '#077031',
                'border-color' => '#077031',
                'color' => ' #fff',
            ],
        ],
        self::STATUS_SURVEY_DONE =>  [
            'base' => [
                'background-color' => '#065224',
                'border-color' => '#065224',
                'color' => ' #fff',
            ],
        ],
        self::STATUS_LOST_SALES_NEW => [
            'base' => [
                'background-color' => '#d93737',
                'border-color' => '#d93737',
                'color' => ' #fff',
            ],
        ],
        self::STATUS_LOST_MASTER =>  [
            'base' => [
                'background-color' => '#d93737',
                'border-color' => '#d93737',
                'color' => ' #fff',
            ],
        ],
        self::STATUS_LOST_CUSTOMER =>  [
            'base' => [
                'background-color' => '#d93737',
                'border-color' => '#d93737',
                'color' => ' #fff',
            ],
        ],
        self::STATUS_LOST_NO_INSTALLER =>  [
            'base' => [
                'background-color' => '#d93737',
                'border-color' => '#d93737',
                'color' => ' #fff',
            ],
        ],
        self::STATUS_LOST_DISPATCHER =>  [
            'base' => [
                'background-color' => '#d93737',
                'border-color' => '#d93737',
                'color' => ' #fff',
            ],
        ],
        self::STATUS_LOST_NOT_APPROVED =>  [
            'base' => [
                'background-color' => '#d93737',
                'border-color' => '#d93737',
                'color' => ' #fff',
            ],
        ],
        self::STATUS_LOST_SALES =>  [
            'base' => [
                'background-color' => '#d93737',
                'border-color' => '#d93737',
                'color' => ' #fff',
            ],
        ],
        self::STATUS_LOST =>  [
            'base' => [
                'background-color' => '#d93737',
                'border-color' => '#d93737',
                'color' => ' #fff',
            ],
        ],
    ];

    public static $paramsData = [
        'First project',
        'Re-project',
        'Invoice split',
        'Additional works'
    ];

    public const PARAMS_DATA_DEFAULT_WITHOUT_DEAL = 'First project';
    public const PARAMS_DATA_DEFAULT_WITH_DEAL= 'Re-project';

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $dealAt;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $paidAt;

    /**
     * @var int
     * @ORM\Column(type="smallint", options={"unsigned"=true, "default"=0})
     */
    protected $status = self::STATUS_NONE;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\User\User")
     * @ORM\JoinColumn(name="author_id", referencedColumnName="id", nullable=true)
     */
    protected $author;

    /**
     * @var string
     * @ORM\Column(type="string", length=12)
     */
    protected $ourNumber;

    /**
     * @var string
     * @ORM\Column(type="string", length=12)
     */
    protected $clientNumber;

    /**
     * @var Master
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\CRM\Master")
     * @ORM\JoinColumn(name="master_id", referencedColumnName="id", nullable=true)
     */
    protected $master;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $closeComment;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\User\User")
     * @ORM\JoinColumn(name="close_id", referencedColumnName="id", nullable=true)
     */
    protected $closeBy;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $closedAt;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $invoice;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\User\User")
     * @ORM\JoinColumn(name="invoice_user_id", referencedColumnName="id", nullable=true)
     */
    protected $invoiceBy;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $invoiceAt;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $invoiceStatusAt;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $amount;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $profit;

    /**
     * @var int
     * @ORM\Column(type="smallint", options={"unsigned"=true, "default"=0})
     */
    protected $dealRating = 0;

    /**
     * @var int
     * @ORM\Column(type="smallint", options={"unsigned"=true, "default"=0})
     */
    protected $reviewStatus = 0;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $reviewStartAt;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $reviewEndAt;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true, length=1024)
     */
    protected $lostComment;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $approvedAt;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\User\User")
     * @ORM\JoinColumn(name="approved_id", referencedColumnName="id", nullable=true)
     */
    protected $approvedBy;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $progressAt;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\User\User")
     * @ORM\JoinColumn(name="progress_id", referencedColumnName="id", nullable=true)
     */
    protected $progressBy;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lostAt;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $param;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    protected $address;


    public function __construct()
    {
        $this->setCreatedAt(new DateTime());
    }

    public function __toString(): string
    {
        return 'Dialog Deal #' . $this->id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCreatedAt(?DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setOurNumber(string $ourNumber): self
    {
        $this->ourNumber = $ourNumber;
        return $this;
    }

    public function getOurNumber(): string
    {
        return $this->ourNumber . '';
    }

    public function setClientNumber(string $clientNumber): self
    {
        $this->clientNumber = $clientNumber;
        return $this;
    }

    public function getClientNumber(): string
    {
        return $this->clientNumber . '';
    }

    public function setComment(?string $comment): self
    {
        if (!$comment) {
            $comment = null;
        }
        $this->comment = $comment;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getCloseComment(): ?string
    {
        return $this->closeComment;
    }

    public function setCloseComment(?string $closeComment): self
    {
        $this->closeComment = $closeComment;
        return $this;
    }

    public function getCloseBy(): ?User
    {
        return $this->closeBy;
    }

    public function setCloseBy(?User $closeBy): self
    {
        $this->closeBy = $closeBy;
        return $this;
    }

    public function getClosedAt(): ?DateTime
    {
        return $this->closedAt;
    }

    public function setClosedAt(?DateTime $closedAt): self
    {
        $this->closedAt = $closedAt;
        return $this;
    }

    public function setDealAt(?DateTime $dealAt): self
    {
        $this->dealAt = $dealAt;
        return $this;
    }

    public function getDealAt(): ?DateTime
    {
        return $this->dealAt;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        // update lostAt date
        if (in_array($status, self::$statusesLost, true)) {
            if (!$this->getLostAt()) {
                $this->setLostAt(new \DateTime());
            }
        } elseif ($this->getLostAt()) {
            $this->setLostAt(null);
        }

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status ?? self::STATUS_NONE;
    }

    public function getStatusName(?int $status = null): string
    {
        if ($status === null) {
            $status = $this->getStatus();
        }
        $name = self::$statusName[$status] ?? '';
        if (!$name) {
            $name = self::$statusNameDrop[$status] ?? '';
        }
        if (!$name) {
            $name = self::$unusedDealStatuses[$status] ?? '';
        }
        return $name;
    }
    public function getLostStatuses(): array
    {
        return self::$statusesLost;
    }

    public function getStatusNameShort(?int $status = null): string
    {
        if ($status === null) {
            $status = $this->getStatus();
        }
        return self::staticStatusNameShort($status ?? 0);
    }

    static public function staticStatusNameShort(int $status): string
    {
        $name = self::$statusNameShort[$status] ?? '';
        if (!$name) {
            $name = self::$statusNameShortDrop[$status] ?? '';
        }
        if (!$name) {
            $name = self::$unusedDealStatuses[$status] ?? '';
        }
        return $name;
    }

    static public function listStatusNamesShort(array $statuses): array
    {
        $return = [];
        foreach ($statuses as $status) {
            $name = self::staticStatusNameShort($status);
            if ($name) {
                $return[] = $name;
            }
        }
        return $return;
    }

    public function getMaster(): ?Master
    {
        return $this->master;
    }

    public function setMaster(?Master $master): self
    {
        $this->master = $master;
        return $this;
    }

    public function getInvoice(): ?string
    {
        return $this->invoice;
    }

    public function setInvoice(?string $invoice): self
    {
        $this->invoice = $invoice;
        return $this;
    }

    public function getInvoiceBy(): ?User
    {
        return $this->invoiceBy;
    }

    public function setInvoiceBy(?User $invoiceBy): self
    {
        $this->invoiceBy = $invoiceBy;
        return $this;
    }

    public function getInvoiceAt(): ?DateTime
    {
        return $this->invoiceAt;
    }

    public function setInvoiceAt(?DateTime $invoiceAt): self
    {
        $this->invoiceAt = $invoiceAt;
        return $this;
    }

    public function setAmount(?int $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setProfit(?int $profit): self
    {
        $this->profit = $profit;
        return $this;
    }

    public function getProfit(): ?int
    {
        return $this->profit;
    }

    public function setDealRating(?int $dealRating): DialogDeal
    {
        $this->dealRating = $dealRating;
        return $this;
    }

    public function setReviewStatus(?int $reviewStatus): DialogDeal
    {
        $this->reviewStatus = $reviewStatus;
        return $this;
    }

    public function setReviewStartAt(?DateTime $reviewStartAt): DialogDeal
    {
        $this->reviewStartAt = $reviewStartAt;
        return $this;
    }

    public function setReviewEndAt(?DateTime $reviewEndAt): DialogDeal
    {
        $this->reviewEndAt = $reviewEndAt;
        return $this;
    }

    public function getDealRating(): ?int
    {
        return $this->dealRating;
    }

    public function getReviewStatus(): ?int
    {
        return $this->reviewStatus;
    }

    public function getReviewStartAt(): ?DateTime
    {
        return $this->reviewStartAt;
    }

    public function getReviewEndAt(): ?DateTime
    {
        return $this->reviewEndAt;
    }

    public function getPaidAt(): ?DateTime
    {
        return $this->paidAt;
    }

    public function setPaidAt(?DateTime $paidAt): DialogDeal
    {
        $this->paidAt = $paidAt;
        return $this;
    }

    public function getReviewStatusName(?int $reviewStatus = null): ?string
    {
        if ($reviewStatus === null) {
            $reviewStatus = $this->getReviewStatus();
        }
        return self::$reviewStatusesName[$reviewStatus] ?? '';

    }

    public function getReviewStatusNameShort(?int $reviewStatus = null): ?string
    {
        if ($reviewStatus === null) {
            $reviewStatus = $this->getReviewStatus();
        }
        return self::$reviewStatusesNameShort[$reviewStatus] ?? '';

    }

    public function getInvoiceStatusAt(): ?DateTime
    {
        return $this->invoiceStatusAt;
    }

    public function setInvoiceStatusAt(?DateTime $invoiceStatusAt): DialogDeal
    {
        $this->invoiceStatusAt = $invoiceStatusAt;
        return $this;
    }

    public function getLostComment(): ?string
    {
        return $this->lostComment;
    }

    public function setLostComment(?string $lostComment): DialogDeal
    {
        $this->lostComment = $lostComment;
        return $this;
    }

    public function isDealCorrection(): bool
    {
        return ($this->status == self::STATUS_DEAL_CORRECTION);
    }

    public function isDealLostNotApproved(): bool
    {
        return ($this->status == self::STATUS_LOST_NOT_APPROVED);
    }

    public function isDealInvoice(): bool
    {
        return $this->status == self::STATUS_INVOICE || $this->status == self::STATUS_GROUP_INVOICE;
    }

    public function getApprovedAt(): ?DateTime
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?DateTime $approvedAt): DialogDeal
    {
        $this->approvedAt = $approvedAt;
        return $this;
    }

    public function getApprovedBy(): ?User
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?User $approvedBy): DialogDeal
    {
        $this->approvedBy = $approvedBy;
        return $this;
    }

    public function getProgressAt(): ?DateTime
    {
        return $this->progressAt;
    }

    public function setProgressAt(?DateTime $progressAt): DialogDeal
    {
        $this->progressAt = $progressAt;
        return $this;
    }

    public function getProgressBy(): ?User
    {
        return $this->progressBy;
    }

    public function setProgressBy(?User $progressBy): DialogDeal
    {
        $this->progressBy = $progressBy;
        return $this;
    }

    public function setLostAt(?DateTime $lostAt): DialogDeal
    {
        $this->lostAt = $lostAt;
        return $this;
    }

    public function getLostAt(): ?DateTime
    {
        return $this->lostAt;
    }

    public function setParam(?string $param): self
    {
        $this->param = $param;
        return $this;
    }

    public function getParam(): ?string
    {
        return $this->param;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

}
