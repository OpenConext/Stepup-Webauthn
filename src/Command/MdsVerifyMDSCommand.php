<?php

declare(strict_types=1);

namespace Surfnet\Webauthn\Command;

use CBOR\Decoder;
use DateTime;
use Surfnet\Webauthn\Repository\MetadataStatementRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Webauthn\AttestationStatement\AttestationObject;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\MetadataService\CertificateChain\CertificateChainValidator;
use Webauthn\MetadataService\CertificateChain\PhpCertificateChainValidator;
use Webauthn\StringStream;

#[AsCommand(
    name: 'mds:verify',
    description: 'Verify downloaded MDS data',
)]
class MdsVerifyMDSCommand extends Command
{
    public function __construct(
        private readonly string $jwtMdsBlobFileName,
        private readonly string $jwtMdsRootCertFileName,
        private readonly string $mdsCacheDir,
        private readonly HttpClientInterface $client,
        private readonly SerializerInterface $serializer
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            "Verify the signature on the MDS data in '" . $this->jwtMdsBlobFileName . " " .
            "using the root certificate in '" . $this->jwtMdsRootCertFileName . "'.\n\n" .
            "You can download (new) MDA data from FIDO using the 'mds:download' console command.\n\n"
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mdsFile = $this->jwtMdsBlobFileName;
        $rootCertFile = $this->jwtMdsRootCertFileName;
        $httpClient = $this->client;
        $serializer = $this->serializer;

        if (!file_exists($mdsFile) || !is_readable($mdsFile)) {
            $output->writeln("MDS file does not exist or is not readable. File: $mdsFile");
            return Command::FAILURE;
        }

        if (!file_exists($rootCertFile) || !is_readable($rootCertFile)) {
            $output->writeln("X.509 root certificate for verifying the MDS file does exist or is not readable. File: $rootCertFile");
            return Command::FAILURE;
        }

        // Use a temporary directory for the MDS cache as not to disturb the original cache
        $mdsCacheDir = sys_get_temp_dir() . '/mds_cache';
        if (!file_exists($mdsCacheDir)) {
            if (!mkdir($mdsCacheDir)) {
                $output->writeln("Failed to create MDS cache directory. Directory: $mdsCacheDir");
                return Command::FAILURE;
            }
        }

        try {
            $output->writeln("Verifying MDS file...");
            $output->writeln("MDS file: $mdsFile");
            $output->writeln("Root certificate: $rootCertFile");

            $mdsRepo = new MetadataStatementRepository($mdsFile, $rootCertFile, $mdsCacheDir,
                new PhpCertificateChainValidator($httpClient), $serializer);

            $output->writeln("MDS file verified OK");

            return Command::SUCCESS;

        } finally {
            $output->writeln("Cleaning up temporary cache directory...");
            Utils::recursivelyRemoveDirectory($mdsCacheDir);
            $output->writeln("Done.");
        }
    }

}