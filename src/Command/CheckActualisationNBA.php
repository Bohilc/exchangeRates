<?php

namespace App\Command;


use App\Entity\RatesArchive;
use App\Repository\CurrencyRepository;
use App\Repository\RatesArchiveRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckActualisationNBA extends Command
{
    protected static $defaultName = 'app:check:act-nbp';

    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;
    /**
     * @var RatesArchiveRepository
     */
    private $archiveRepository;
    /**
     * @var ObjectManager
     */
    private $manager;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var LoggerInterface
     */
    private $logger;

    private $cache;

    public function __construct(
        CurrencyRepository $currencyRepository,
        RatesArchiveRepository $archiveRepository,
        ObjectManager $manager,
        LoggerInterface $logger)
    {

        $this->currencyRepository = $currencyRepository;
        $this->archiveRepository = $archiveRepository;
        $this->manager = $manager;
        $this->cache = new Filesystemcache();

        parent::__construct();
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setDescription("Check actualisation NBP")
            ->setHelp("This command check actualisation API from NBP");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->checkActualisationNBP());
    }

    /**
     * Check actualisation exchange rate
     * @return bool
     */
    public function checkActualisationNBP()
    {
        $hasChanges = false;

        $nbp_tables = ['a', 'b'];

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
                    echo $log;
                }
            }
        }

        if ($hasChanges === false) {
            echo 'Nothing to update';
        } else {
            $this->cache->clear();
            echo '   /-/-/-/-/-/-/-/ The data has been updated';
        }

        return $hasChanges;
    }

    /**
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
     * @param $currency
     * @param $newMid
     */
    public function updateExchangeRate($currency, $newMid)
    {
        $rates_archive = new RatesArchive();
        $rates_archive->setCurrency($currency);
        $rates_archive->setMid($newMid);
        $rates_archive->updatedTimestamps();

        $this->manager->persist($rates_archive);
        $this->manager->flush();
    }
}