<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CurrencyRepository")
 */
class Currency implements JsonSerializable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    private $currency;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $code;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $update_at;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\RatesArchive", mappedBy="currency")
     * @ORM\OrderBy({"created_at" = "DESC"})
     */
    private $ratesArchives;


    public function __construct()
    {
        $this->ratesArchives = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function jsonSerialize($avg = false): array
    {
        return [
            'code' => $this->code,
            'currency' => $this->currency,
            'mid' => $avg ? $this->getAvgRateFromAllTime() : $this->getRatesArchives()->first()->getMid()
        ];
    }


    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps(): void
    {
        $this->setUpdateAt(new \DateTime('now'));
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdateAt(): ?\DateTimeInterface
    {
        return $this->update_at;
    }

    public function setUpdateAt(\DateTimeInterface $update_at): self
    {
        $this->update_at = $update_at;

        return $this;
    }

    /**
     * @return Collection|RatesArchive[]
     */
    public function getRatesArchives(): Collection
    {
        return $this->ratesArchives;
    }

    public function addRatesArchive(RatesArchive $ratesArchive): self
    {
        if (!$this->ratesArchives->contains($ratesArchive)) {
            $this->ratesArchives[] = $ratesArchive;
            $ratesArchive->setCurrency($this);
        }

        return $this;
    }

    public function removeRatesArchive(RatesArchive $ratesArchive): self
    {
        if ($this->ratesArchives->contains($ratesArchive)) {
            $this->ratesArchives->removeElement($ratesArchive);

            if ($ratesArchive->getCurrency() === $this) {
                $ratesArchive->setCurrency(null);
            }
        }

        return $this;
    }

    public function getAvgRateFromAllTime()
    {
        $currencyRatesSum = 0;
        $archiveCurrencyRate = $this->getRatesArchives()->getValues();

        foreach ($archiveCurrencyRate as $currencyRate) {
            $currencyRatesSum += $currencyRate->getMid();
        }

        $avgRate = $currencyRatesSum / count($archiveCurrencyRate);

        return $avgRate;
    }
}
