<?php declare(strict_types=1);

/*
 * This file is part of the 2amigos/mail-service.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Application\Console\Command;

use App\Infrastructure\Console\ColorizedTrait;
use Exception;
use Gettext\Translations;
use GuzzleHttp\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportPoEditorTranslationsCommand extends Command
{
    use ColorizedTrait;

    /**
     * @inheritdoc
     *
     * @throws \InvalidArgumentException
     */
    protected function configure(): void
    {
        $this
            ->setName('import-translations:run')
            ->setDescription('Import POEditor translations command')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> run command:
<comment>Run</comment>
    <info>php %command.full_name% --api-token=ACDEDFEGKDLDIK --languages=de,ru --project=console --delay=30</info>
EOF
            )
            ->addOption('api-token', 't', InputOption::VALUE_REQUIRED, 'POEditor api token?')
            ->addOption('project', 'p', InputOption::VALUE_REQUIRED, 'POEditor project id?')
            ->addOption('languages', 'l', InputOption::VALUE_REQUIRED, 'POEditor languages?')
            ->addOption('delay', 'd', InputOption::VALUE_OPTIONAL, 'Seconds delay between language imports?', 5);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \LogicException
     * @throws Exception
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setColors($output);

        // in case you wish to use file logs
        // $logDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $directories = explode(',', $input->getOption('languages'));
        $loggerName = 'translations-importer';
        $logger = new Logger($loggerName);
        $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        $logger->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));

        $client = new Client(['base_uri' => 'https://api.poeditor.com/v2/']);

        $output->writeln('<g>*************** Import POEditor Translations Command ***************</g>');

        foreach ($directories as $directory) {
            $language = $this->getLanguageData($directory);
            $output->writeln("<b>Requesting '$language'</b>");

            $response = $client->request(
                'POST',
                'projects/export',
                [
                    'form_params' => [
                        'api_token' => $input->getOption('api-token'),
                        'id' => $input->getOption('project'),
                        'language' => $language,
                        'type' => 'po',
                    ],
                ]
            );

            $data = json_decode($response->getBody(), true);

            if ($data['response']['code'] !== '200' || empty($data['result']['url'])) {
                $logger->error('Language: ' . $language . ', Message: ' . $data['response']['message']);
                throw new Exception($data['response']['message']);
            }

            // Save file locally
            if (!is_dir($directory) && !mkdir($directory) && !is_dir($directory)) {
                die('Unable to create holding directory!');
            }

            $output->writeln('<b>Save response in .po file</b>');

            $resource = fopen($directory . '/messages.po', 'wb');
            $fileResponse = $client->request('GET', $data['result']['url'], ['sink' => $resource]);

            // Convert from PO to php arrays
            $output->writeln('<b>Convert it into .php array file</b>');

            $t = Translations::fromPoFile($directory . '/messages.po');
            $t->toPhpArrayFile($directory . '/messages.php');
            unlink($directory . '/messages.po');

            $output->writeln('<b>Done</b>');

            $logger->info('Language:' . $language . ', Message: ' . $fileResponse->getReasonPhrase());
        }
    }

    /**
     * Get language and directory name
     *
     * @param string $dir
     *
     * @return string
     */
    private function getLanguageData($dir): string
    {
        $explodedPath = explode('/', $dir);

        return end($explodedPath);
    }
}
