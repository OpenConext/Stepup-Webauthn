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
    name: 'mds:lookup',
    description: 'Lookup an aaguid in the MDS',
)]
class MdsLookupCommand extends Command
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
            "Lookup an aaguid in the MDS data in '" . $this->jwtMdsBlobFileName . "'.\n" .
            "You can download (new) MDS data from FIDO using the 'mds:download' console command.\n\n"
        );

        $this->addArgument('aaguid', InputArgument::REQUIRED, 'aaguid to lookup. Example: ee041bce-25e5-4cdb-8f86-897fd6418464');
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
            $output->writeln("X.509 root certificate for the MDS file does exist or is not readable. File: $mdsFile");
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

            // Use the existing MetadataStatementRepository
            $mdsRepo = new MetadataStatementRepository($mdsFile, $rootCertFile, $mdsCacheDir,
                new PhpCertificateChainValidator($httpClient), $serializer);

            $output->writeln("MDS file verified OK");

            // The PEM X.509 root certificates for the authenticator from the MDS file
            $rootCerts = array();

            $aaguid = $input->getArgument('aaguid');
            $output->writeln("Looking up metadata for AAGUID: $aaguid");
            if (!$mdsRepo->has($aaguid)) {
                $output->writeln("AAGUID not found in MDS");
                return Command::FAILURE;
            }
            $out = $mdsRepo->get($aaguid);
            $output->writeln( json_encode($out, JSON_PRETTY_PRINT) );

            $output->writeln("\nAttestation root certificates:");
            $nr=0;
            foreach ($out->attestationRootCertificates as $cert) {
                $nr++;
                $output->writeln("#$nr:");
                $certPEM = Utils::base64ToPEMCert($cert);
                $cert_parsed=openssl_x509_parse($certPEM);
                if (!$cert_parsed) {
                    $output->writeln("Failed to parse certificate:");
                    $output->writeln($certPEM);
                    continue;
                }
                $rootCerts[] = $certPEM;

                $output->writeln(Utils::X509toString($cert_parsed));
                $certDER=base64_decode($cert);
                $sha1=hash('sha1',$certDER,false);
                $sha256=hash('sha256',$certDER,false);
                $output->writeln("Fingerprint: SHA-1=$sha1; SHA-256=$sha256");
                $output->writeln("");
            }

            return Command::SUCCESS;

        } finally {
            $output->writeln("Cleaning up temporary cache directory...");
            Utils::recursivelyRemoveDirectory($mdsCacheDir);
            $output->writeln("Done.");
        }
    }
}