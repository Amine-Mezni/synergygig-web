<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Applications;

#[ORM\Entity]
class Contracts
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

        #[ORM\ManyToOne(targetEntity: Applications::class, inversedBy: "contractss")]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Applications $application_id;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $start_date;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $end_date;

    #[ORM\Column(type: "float")]
    private float $amount;

    #[ORM\Column(type: "text")]
    private string $terms;

    #[ORM\Column(type: "string")]
    private string $status;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "string", length: 255)]
    private string $payment_intent_id;

    #[ORM\Column(type: "string")]
    private string $payment_status;

    #[ORM\Column(type: "string", length: 255)]
    private string $blockchain_hash;

    #[ORM\Column(type: "float")]
    private float $risk_score;

    #[ORM\Column(type: "text")]
    private string $ai_summary;

    #[ORM\Column(type: "text")]
    private string $ai_analysis;

    #[ORM\Column(type: "text")]
    private string $ai_improved;

    #[ORM\Column(type: "text")]
    private string $ai_full_contract;

    #[ORM\Column(type: "string", length: 64)]
    private string $fingerprint;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getApplication_id()
    {
        return $this->application_id;
    }

    public function setApplication_id($value)
    {
        $this->application_id = $value;
    }

    public function getStart_date()
    {
        return $this->start_date;
    }

    public function setStart_date($value)
    {
        $this->start_date = $value;
    }

    public function getEnd_date()
    {
        return $this->end_date;
    }

    public function setEnd_date($value)
    {
        $this->end_date = $value;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount($value)
    {
        $this->amount = $value;
    }

    public function getTerms()
    {
        return $this->terms;
    }

    public function setTerms($value)
    {
        $this->terms = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }

    public function getPayment_intent_id()
    {
        return $this->payment_intent_id;
    }

    public function setPayment_intent_id($value)
    {
        $this->payment_intent_id = $value;
    }

    public function getPayment_status()
    {
        return $this->payment_status;
    }

    public function setPayment_status($value)
    {
        $this->payment_status = $value;
    }

    public function getBlockchain_hash()
    {
        return $this->blockchain_hash;
    }

    public function setBlockchain_hash($value)
    {
        $this->blockchain_hash = $value;
    }

    public function getRisk_score()
    {
        return $this->risk_score;
    }

    public function setRisk_score($value)
    {
        $this->risk_score = $value;
    }

    public function getAi_summary()
    {
        return $this->ai_summary;
    }

    public function setAi_summary($value)
    {
        $this->ai_summary = $value;
    }

    public function getAi_analysis()
    {
        return $this->ai_analysis;
    }

    public function setAi_analysis($value)
    {
        $this->ai_analysis = $value;
    }

    public function getAi_improved()
    {
        return $this->ai_improved;
    }

    public function setAi_improved($value)
    {
        $this->ai_improved = $value;
    }

    public function getAi_full_contract()
    {
        return $this->ai_full_contract;
    }

    public function setAi_full_contract($value)
    {
        $this->ai_full_contract = $value;
    }

    public function getFingerprint()
    {
        return $this->fingerprint;
    }

    public function setFingerprint($value)
    {
        $this->fingerprint = $value;
    }
}
