<?php
/**
 * Created by PhpStorm.
 * User: bohilc
 * Date: 09.03.19
 * Time: 14:53
 */

namespace App\DataFixtures;


use App\Entity\Currency;
use App\Entity\RatesArchive;
use App\Repository\CurrencyRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class ExchangeRatesFixtures extends Fixture
{
    /**
     * @var CurrencyRepository
     */
    private $exchangeRatesRepository;
    /**
     * @var ObjectManager
     */
    private $manager;

    public function __construct(CurrencyRepository $exchangeRatesRepository, ObjectManager $manager)
    {

        $this->exchangeRatesRepository = $exchangeRatesRepository;
        $this->manager = $manager;
    }

    public function load(ObjectManager $manager)
    {
        $nbp_tables = ['a', 'b'];

        foreach ($nbp_tables as $nbp_table) {
            $nbp_response = $this->nbpApiTable($nbp_table);

            foreach ($nbp_response[0]['rates'] as $rate) {
                $currency = new Currency();
                $currency->setCurrency($rate['currency']);
                $currency->setCode($rate['code']);
                $currency->updatedTimestamps();

                $this->manager->persist($currency);
                $this->manager->flush();

                $rates_archive = new RatesArchive();
                $rates_archive->setCurrency($currency);
                $rates_archive->setMid($rate['mid']);
                $rates_archive->updatedTimestamps();

                $this->manager->persist($rates_archive);
                $this->manager->flush();

            }
        }

    }

    public function nbpApiTable($table): array
    {
        $url = "http://api.nbp.pl/api/exchangerates/tables/$table?format=json";
        $data = file_get_contents($url);
        return json_decode($data, true);
    }
}