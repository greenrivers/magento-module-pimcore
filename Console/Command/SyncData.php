<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Console\Command;

use Greenrivers\PimcoreIntegration\Model\Queue\Product\Publisher as ProductPublisher;
use Greenrivers\PimcoreIntegration\Model\Queue\Category\Publisher as CategoryPublisher;
use Greenrivers\PimcoreIntegration\Service\GraphqlService;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncData extends Command
{
    public const LIMIT_PRODUCTS = 100;
    public const LIMIT_CATEGORIES = 100;

    private const COMMAND_NAME = 'greenrivers:pimcore:sync';

    private const PRODUCTS_OPTION = 'products';
    private const CATEGORIES_OPTION = 'categories';

    /**
     * SyncData constructor.
     * @param GraphqlService $graphqlService
     * @param ProductPublisher $productPublisher
     * @param CategoryPublisher $categoryPublisher
     * @param ProgressBarFactory $progressBarFactory
     * @param State $state
     * @param LoggerInterface $logger
     * @param string $name
     */
    public function __construct(
        private readonly GraphqlService     $graphqlService,
        private readonly ProductPublisher   $productPublisher,
        private readonly CategoryPublisher  $categoryPublisher,
        private readonly ProgressBarFactory $progressBarFactory,
        private readonly State              $state,
        private readonly LoggerInterface    $logger,
        string                              $name = self::COMMAND_NAME
    )
    {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Synchronize data from Pimcore to Magento.');
        $this->addOption(
            self::PRODUCTS_OPTION,
            'p',
            InputOption::VALUE_NONE,
            'Get products data.'
        );
        $this->addOption(
            self::CATEGORIES_OPTION,
            'c',
            InputOption::VALUE_NONE,
            'Get categories data.'
        );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (LocalizedException $e) {
            $output->writeln('<error>Error set area code</error>');
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $this->logger->error($e->getMessage());
        }

        $productsOption = $input->getOption(self::PRODUCTS_OPTION);
        $categoriesOption = $input->getOption(self::CATEGORIES_OPTION);
        $progressBarProducts = $this->progressBarFactory->create(
            [
                'output' => $output,
                'max' => 100
            ]
        );
        $progressBarCategories = $this->progressBarFactory->create(
            [
                'output' => $output,
                'max' => 100
            ]
        );

        if ($productsOption) {
            try {
                $this->processProducts($progressBarProducts);
                $output->writeln(' <info>Products synchronized.</info>');
            } catch (GuzzleException $e) {
                $output->writeln('<error>Error get products from Pimcore</error>');
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                $this->logger->error($e->getMessage());
            }
        }

        if ($categoriesOption) {
            try {
                $this->processCategories($progressBarCategories);
                $output->writeln(' <info>Categories synchronized.</info>');
            } catch (GuzzleException $e) {
                $output->writeln('<error>Error get categories from Pimcore</error>');
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                $this->logger->error($e->getMessage());
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param ProgressBar $progressBar
     * @return void
     * @throws GuzzleException
     */
    private function processProducts(ProgressBar $progressBar): void
    {
        $data = ['first' => self::LIMIT_PRODUCTS];
        $products = $this->graphqlService->getProducts($data);
        $offset = self::LIMIT_PRODUCTS;

        foreach ($products as $product) {
            $this->productPublisher->publish($product['node']);
        }

        while (count($products) === self::LIMIT_PRODUCTS) {
            $data = ['first' => self::LIMIT_PRODUCTS, 'after' => $offset];
            $products = $this->graphqlService->getProducts($data);
            $offset += count($products);
            $progressBar->advance();

            foreach ($products as $product) {
                $this->productPublisher->publish($product['node']);
            }
        }

        $progressBar->finish();
    }

    /**
     * @param ProgressBar $progressBar
     * @return void
     * @throws GuzzleException
     */
    private function processCategories(ProgressBar $progressBar): void
    {
        $data = ['first' => self::LIMIT_CATEGORIES];
        $categories = $this->graphqlService->getCategories($data);
        $offset = self::LIMIT_CATEGORIES;

        foreach ($categories as $category) {
            $this->categoryPublisher->publish($category['node']);
        }

        while (count($categories) === self::LIMIT_CATEGORIES) {
            $data = ['first' => self::LIMIT_CATEGORIES, 'after' => $offset];
            $categories = $this->graphqlService->getCategories($data);
            $offset += count($categories);
            $progressBar->advance();

            foreach ($categories as $category) {
                $this->categoryPublisher->publish($category['node']);
            }
        }

        $progressBar->finish();
    }
}
