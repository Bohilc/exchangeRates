<?php

namespace App\Controller;

use App\Entity\RatesArchive;
use App\Repository\CurrencyRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("api/exchangerates")
 *
 * @return Response
 */
class ExchangeRateController extends FOSRestController
{

    /**
     * @var ObjectManager
     */
    private $manager;
    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;

    private $today;

    /**
     * Tables of foreign exchange rates is updated every Wednesday between 11:45 and 12:15
     * @var string
     */
    private $dataActNBPFrom = '11:45';

    /**
     * Tables of foreign exchange rates is updated every Wednesday between 11:45 and 12:15
     * @var string
     */
    private $dataActNBPTo = '12:15';
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var FilesystemCache
     */
    private $cache;


    public function __construct(
        ObjectManager $manager,
        CurrencyRepository $currencyRepository,
        LoggerInterface $logger)
    {
        $this->manager = $manager;
        $this->currencyRepository = $currencyRepository;
        $this->today['day'] = date('D', time());
        $this->today['hour'] = date('H:i', time());
        $this->logger = $logger;
        $this->cache = new FilesystemCache();

        /**
         * Table A of foreign exchange rates is updated every day between 11:45 and 12:15
         * More information in: http://www.nbp.pl/home.aspx?f=/statystyka/kursy.html
         */
        $this->checkActualisationNBP();
        if ($this->today['day'] != 'Sun' && $this->today['day'] != 'Sat') {
            if ($this->today['hour'] >= $this->dataActNBPFrom && $this->today['hour'] <= $this->dataActNBPTo) {
                $this->checkActualisationNBP();
            }
        }
    }

    /**
     * @Rest\Get("/")
     *
     * @return Response
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function exchangeRates()
    {
        if (!$this->cache->has('currencies')) {
            $exchange_rates = [];

            $allExchangeRates = $this->currencyRepository->findAll();

            foreach ($allExchangeRates as $currency) {
                $data['code'] = $currency->getCode();
                $data['currency'] = $currency->getCurrency();
                $data['mid'] = $currency->getRatesArchives()->first()->getMid();
                array_push($exchange_rates, $data);
            }

            $this->cache->set("currencies", $exchange_rates);
        }

        return new JsonResponse($this->cache->get('currencies'), JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/{currency}")
     *
     * @param $currency
     *
     * @return Response
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function currency($currency)
    {
        if (!$this->cache->has("currency_$currency")) {
            $exchange_rates = [];
            $currencyRepository = $this->currencyRepository->findOneBy(['code' => $currency]);

            if ($currencyRepository) {
                $data['code'] = $currencyRepository->getCode();
                $data['currency'] = $currencyRepository->getCurrency();
                $data['mid'] = $currencyRepository->getRatesArchives()->first()->getMid();
                array_push($exchange_rates, $data);
                $this->cache->set("currency_$currency", $exchange_rates);
            }
        }

        return new JsonResponse($this->cache->get("currency_$currency"), JsonResponse::HTTP_OK);
    }

    /**
     * @Rest\Get("/avgcurrency/{currency}")
     *
     * @param $currency
     *
     * @return Response
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function midCurrency($currency)
    {
        if (!$this->cache->has("avgcurrency_$currency")) {
            $exchange_rates = [];
            $currencyRepository = $this->currencyRepository->findOneBy(['code' => $currency]);

            if ($currencyRepository) {
                $data['code'] = $currencyRepository->getCode();
                $data['currency'] = $currencyRepository->getCurrency();
                $data['mid'] = $currencyRepository->getAvgRateFromAllTime();
                array_push($exchange_rates, $data);
                $this->cache->set("avgcurrency_$currency", $exchange_rates);
            }
        }

        return new JsonResponse($this->cache->get("avgcurrency_$currency"), JsonResponse::HTTP_OK);
    }

    /**
     * Check actualisation exchange rate
     * @return bool
     */
    public function checkActualisationNBP()
    {
        $hasChanges = false;
        // Table A of foreign exchange rates is updated every day between 11:45 and 12:15
        $nbp_tables = ['a'];
        // Table B of foreign exchange rates is updated every Wednesday between 11:45 and 12:15
        if ($this->today['day'] == 'Wed') {
            array_push($nbp_response, 'b');
        }

        foreach ($nbp_tables as $nbp_table) {
            $nbp_response = $this->nbpApiTable($nbp_table);

            if (!count($nbp_response)) {
                return false;
            }

            foreach ($nbp_response[0]['rates'] as $rate) {
                $currency = $this->currencyRepository->findOneBy(['code' => $rate['code']]);
                $mid = $currency->getRatesArchives()->first()->getMid();

                if ($rate['mid'] != $mid) {
                    $hasChanges = true;
                    $this->updateExchangeRate($currency, $rate['mid']);
                    $log = $currency->getCode() . " - " . $currency->getCurrency() . ". From $mid to $rate[mid]";
                    $this->logger->info('Actualisation data NBP: ' . $log);
                }
            }
        }

        if ($hasChanges) {
            $this->cache->clear();
        }

        return $hasChanges;
    }

    /**
     * Get data from API NBP
     *
     * @param $table
     *
     * @return array
     */
    public function nbpApiTable($table): array
    {
        $url = getenv('NBP_TABLES') . "$table?format=json";
        $data = @file_get_contents($url);
        if (!$data) {
            $data = '{}';
        }
        return json_decode($data, true);
    }


    /**
     * Update Exchange rate
     *
     * @param $currency
     * @param $newMid
     *
     * @return RatesArchive
     */
    public function updateExchangeRate($currency, $newMid): RatesArchive
    {
        $rates_archive = new RatesArchive();
        $rates_archive->setCurrency($currency);
        $rates_archive->setMid($newMid);
        $rates_archive->updatedTimestamps();

        $this->manager->persist($rates_archive);
        $this->manager->flush();

        return $rates_archive;
    }
}
