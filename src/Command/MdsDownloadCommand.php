<?php

declare(strict_types=1);

namespace Surfnet\Webauthn\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mds:download',
    description: 'Downloads MDS data',

)]
class MdsDownloadCommand extends Command
{
    const MDS_METADATA_URL = 'https://mds3.fidoalliance.org/blob.jwt';

    public function __construct(
        private readonly string $jwtMdsBlobFileName,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Downloads MDS data from "' . self::MDS_METADATA_URL . '" and saves it to "' . $this->jwtMdsBlobFileName . '".');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mdsFile = $this->jwtMdsBlobFileName;

        // Get directory of mdsFile
        $mdsDir = dirname($mdsFile);

        if (!is_dir($mdsDir) || !is_writable($mdsDir)) {
            $output->writeln('MDS directory does not exist or is not writeable');
            return Command::FAILURE;
        }
        $output->writeln("Starting MDS download from " . self::MDS_METADATA_URL );

        // Create temporary directory
        $tempDir = sys_get_temp_dir();
        $tempMdsFile = $tempDir . '/blob.jwt';

        try {
            // Use Guzzle to download the MDS metadata to $tempDir
            $client = new \GuzzleHttp\Client(
                ['allow_redirects' => true]
            );
            $response = $client->request('GET', self::MDS_METADATA_URL,
                ['sink' => $tempMdsFile]);

            $status = $response->getStatusCode();
            if ($status != 200) {
                $output->writeln("MDS download failed with HTTP status $status");
                return Command::FAILURE;
            }

            // Move mds file into place
            rename($tempMdsFile, $mdsFile);

            $output->writeln('Wrote MDS file to ' . $mdsFile);
            return Command::SUCCESS;
        } finally {
            // Cleanup temp file if exists, temp dir is OS business.
            if (file_exists($tempMdsFile)) {
                unlink($tempMdsFile);
            }
        }

    }
}