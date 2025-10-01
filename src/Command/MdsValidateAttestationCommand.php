<?php

declare(strict_types=1);

namespace Surfnet\Webauthn\Command;

use CBOR\Decoder;
use DateTime;
use Nyholm\Psr7\Uri;
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
    name: 'mds:x',
    description: 'Validate an attestationObject against the MDS data',
)]
class MdsValidateAttestationCommand extends Command
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
            "Validate an attestation object against the MDS data in '" . $this->jwtMdsBlobFileName . "'.\n" .
            "You can download (new) MDA data from FIDO using the 'mds:download' console command.\n\n"
        );

        // Example matching attestationObject:
        // o2NmbXRmcGFja2VkZ2F0dFN0bXSjY2FsZyZjc2lnWEgwRgIhAIx8dI3XXeLkMJ_ax8ymBq3oqCahgzQ2vk2KBBv0KhjTAiEA_5LGQFTKgwsA9mg13SBo2G4ak-6BlDHH3Bwlhv3ad6NjeDVjglkCQTCCAj0wggHjoAMCAQICEB3ytVpR3EtohaPZnml_7RwwCgYIKoZIzj0EAwIwSTELMAkGA1UEBhMCVVMxHTAbBgNVBAoMFEZlaXRpYW4gVGVjaG5vbG9naWVzMRswGQYDVQQDDBJGZWl0aWFuIEZJRE8gQ0EgMDQwIBcNMjEwNDE1MDAwMDAwWhgPMjAzNjA0MTQyMzU5NTlaMGoxCzAJBgNVBAYTAlVTMR0wGwYDVQQKDBRGZWl0aWFuIFRlY2hub2xvZ2llczEiMCAGA1UECwwZQXV0aGVudGljYXRvciBBdHRlc3RhdGlvbjEYMBYGA1UEAwwPRlQgRklETzIgMDQzMCBKMFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEpvnB-zUYIJrHvy1-asWAR4ge39jEAOwZ_zT7P1S0fXaYjeAiOoEpg8uTBlUgxOA7IDb51CkYfMbb-fNQwL3nmaOBiTCBhjAdBgNVHQ4EFgQUQTN9GEOYgawJ1mkABzxlom7kRjUwHwYDVR0jBBgwFoAUkyNwZsUdzsSrHCuthMHz5x3OYGcwDAYDVR0TAQH_BAIwADATBgsrBgEEAYLlHAIBAQQEAwIEMDAhBgsrBgEEAYLlHAEBBAQSBBDuBBvOJeVM24-GiX_WQYRkMAoGCCqGSM49BAMCA0gAMEUCIQDM7FGZBP4FTbwsseT2tLCe_ZKTPF9D3dQ10qpAE_194wIgFp9Px9qokqQMIa3QxgLtbwpN5gVjrjQWcqtO0xfQbO9ZAf4wggH6MIIBoKADAgECAhAYFStBt0OubbQVmcOxfYIJMAoGCCqGSM49BAMCMEsxCzAJBgNVBAYTAlVTMR0wGwYDVQQKDBRGZWl0aWFuIFRlY2hub2xvZ2llczEdMBsGA1UEAwwURmVpdGlhbiBGSURPIFJvb3QgQ0EwIBcNMTgwNTIwMDAwMDAwWhgPMjAzODA1MTkyMzU5NTlaMEkxCzAJBgNVBAYTAlVTMR0wGwYDVQQKDBRGZWl0aWFuIFRlY2hub2xvZ2llczEbMBkGA1UEAwwSRmVpdGlhbiBGSURPIENBIDA0MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAExaEWVjmKkhb8crsoukpphTm_j0crBmzIQCqdpJ_QJDPrtUdnRw9dh3qcTi6bcEfSWvhbziA9xkVR6tnbceu4M6NmMGQwHQYDVR0OBBYEFJMjcGbFHc7EqxwrrYTB8-cdzmBnMB8GA1UdIwQYMBaAFEu9hyYRrRyJzwRYvnDSCIxrFiO3MBIGA1UdEwEB_wQIMAYBAf8CAQAwDgYDVR0PAQH_BAQDAgEGMAoGCCqGSM49BAMCA0gAMEUCIH-1QMQ_RpYWJL0TJUi0St8EtmFxj-QsMrpfmtQMcGq1AiEA-sWmfdzVx_eRWKQZBWiZW67HU-J6lUpOMiH3nkpgwvFoYXV0aERhdGFY5M8ZRny6AJ-VL1V31INK9X7n5ljlltgLN2KeqII_5ovZRQAGAjTuBBvOJeVM24-GiX_WQYRkAGDIuoYWHy4CkZ4sDlmvqk8JrapSPjq5Bz8mhWBx1Y65OwelS1lpwdSQH0tqg6_MndhUIgvNEG2Fhad8IVEizRT3qlCaaHwnaMXS6UzfxUTxv6oA5oRSy04KY9h_MQtnl-alAQIDJiABIVggAq-wMK3Bxa9Iq9u7LKd-G95GH9ppyqfT__A4ATE-QYgiWCCtU_HAxqRTzhY9z4yebK8Ddb96gArezBC5HaBN6jgR_Q
        $this->addArgument('attestationObject', InputArgument::REQUIRED, 'attestationObject to verify, base64url encoded.');
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

            $output->writeln("MDS file verified");

            $output->writeln("Decoding attestationObject...");
            $attestationObject = $input->getArgument('attestationObject');
            $attestationObject = Utils::base64url_decode($attestationObject);
            $output->writeln("Base64url decoding OK");
            $stream=new StringStream($attestationObject);
            $parsed=Decoder::create()->decode($stream);
            $stream->close();
            $output->writeln("AttestationObject decoded OK");
            $a = $parsed->normalize();
            $output->writeln("AttestationObject normalized OK");

            if (!isset($a['authData'])) {
                $output->writeln("authData not found");
                return Command::FAILURE;
            }
            $authData = $a['authData'];
            // Bytes 37-52 contain the AAGUID (16 bytes)
            $aaguid = substr($authData, 37, 16);
            // Convert to hex string and format as UUID
            $aaguidHex = bin2hex($aaguid);
            $aaguidFormatted = sprintf(
                '%s-%s-%s-%s-%s',
                substr($aaguidHex, 0, 8),   // 8 chars (4 bytes)
                substr($aaguidHex, 8, 4),   // 4 chars (2 bytes)
                substr($aaguidHex, 12, 4),  // 4 chars (2 bytes)
                substr($aaguidHex, 16, 4),  // 4 chars (2 bytes)
                substr($aaguidHex, 20, 12)  // 12 chars (6 bytes)
            );
            $output->writeln("AAGUID: $aaguidFormatted");
            $output->writeln("Looking up $aaguidFormatted in the MDS");

            if (!$mdsRepo->has($aaguidFormatted)) {
                $output->writeln("AAGUID not found in MDS");
                return Command::FAILURE;
            }
            $output->writeln("Found. Getting MDS entry for $aaguidFormatted:");
            $out = $mdsRepo->get($aaguidFormatted);

            $output->writeln( json_encode($out, JSON_PRETTY_PRINT) );

            $output->writeln("\nAttestation root certificates from the MDS metadata:");
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


            if (!isset($a['attStmt'])) {
                $output->writeln("attStmt not found");
                return Command::FAILURE;
            }
            $attStmt = $a['attStmt'];
            if (isset($attStmt['x5c'])) {
                $chainCerts = array();
                // The PEM X.509 certificate chain from the attestation statement
                $output->writeln("Attestation certificate(s) from the token's attestation statement:");
                $nr = 0;
                foreach ($attStmt['x5c'] as $cert) {
                    $nr++;
                    $output->writeln("#$nr:");
                    $certPEM = Utils::base64ToPEMCert(base64_encode($cert));
                    $cert_parsed = openssl_x509_parse($certPEM);
                    if (!$cert_parsed) {
                        $output->writeln("Failed to parse certificate:");
                        $output->writeln($certPEM);
                        continue;
                    }
                    $chainCerts[] = $certPEM;
                    $output->writeln(Utils::X509toString($cert_parsed));

                }

                $output->writeln("\nValidating attestation chain certificates against the trusted root certificates from MDS");
                $output->writeln("using \Webauthn\MetadataService\CertificateChain\PhpCertificateChainValidator");

                $chainValidator = new PhpCertificateChainValidator($httpClient);

                try {
                    $chainValidator->check($chainCerts, $rootCerts);
                } catch (\Exception $e) {
                    $output->writeln('--------------------------------------------------------');
                    $output->writeln("ERROR: Attestation chain certificates validation failed:");
                    $output->writeln($e->getMessage());
                    $output->writeln($e->getTraceAsString());
                    $output->writeln('--------------------------------------------------------');

                    $output->writeln("");
                    $output->writeln("--== Start debug analysis of the certificate chain using openssl_x509_verify() ==--");
                    self::debugCertificateChain($output, $rootCerts, $chainCerts);
                    $output->writeln("--== End debug analysis of the certificate chain using openssl_x509_verify() ==--");

                    return Command::FAILURE;
                }
                $output->writeln("Attestation chain certificates validated");
            }
            return Command::SUCCESS;

        } finally {
            $output->writeln("Cleaning up temporary cache directory...");
            Utils::recursivelyRemoveDirectory($mdsCacheDir);
            $output->writeln("Done.");
        }
    }

    static function debugCertificateChain(OutputInterface $output, array $trusedCAs, array $chain) : void
    {
        $intermediateCAs = array();
        $cas = array();
        $caCertNo = 0;
        $nrTrustedCAs = sizeof($trusedCAs);
        $output->writeln("Checking $nrTrustedCAs trusted CAs:");
        foreach ($trusedCAs as $cert) {
            $output->writeln("Checking CA cert $caCertNo:");
            $parsed = openssl_x509_read($cert);    // We've done this before, should not fail
            if (!$parsed) {
                $output->writeln("Failed to parse certificate:");
                $output->writeln($cert);
                return;
            }
            $hash=openssl_x509_fingerprint($parsed, 'sha1');
            if (isset($cas[$hash])) {
                $output->writeln("Duplicate certificate found in the trusted CAs.");
            }
            // Check if this is a self-signed certificate
            if (1 !== openssl_x509_verify($parsed, $parsed)) {
                $output->writeln("Certificate is not self-signed, moving it to the intermediate CA list");
                $intermediateCAs[]=$cert;   // TODO: Handle intermediates in the MDS
                $cas[$hash] = $parsed;
            }
            else {
                $output->writeln("OK: CA cert $hash is a self-signed certificate");
                $cas[$hash] = $parsed;
            }
            $output->writeln($cert);
            $caCertNo++;
        }

        // Process chain certs
        $nrChainCerts = sizeof($chain);
        $output->writeln("Checking $nrChainCerts chain certs:");
        $chainCertNo = 0;
        $cs = array();
        foreach ($chain as $cert) {
            $output->writeln("Checking chain cert $chainCertNo:");
            $parsed = openssl_x509_read($cert);    // We've done this before, should not fail
            if (!$parsed) {
                $output->writeln("Failed to parse certificate:");
                $output->writeln($cert);
                return;
            }
            $hash=openssl_x509_fingerprint($parsed, 'sha1');
            if (isset($cs[$hash])) {
                $output->writeln("ERROR: Duplicate certificate found in the chain.");
            }
            $cs[$hash] = $parsed;
            // Check if this is a self-signed certificate
            if (1 === openssl_x509_verify($parsed, $parsed)) {
                $output->writeln("ERROR: Self-signed certificate in the chain.");
            }
            else {
                $output->writeln("OK: Chain cert $hash is not a self-signed certificate");
            }
            $cert_parsed=openssl_x509_parse($parsed);
            $output->writeln(Utils::X509toString($cert_parsed));
            $output->writeln($cert);
        }

        $output->writeln("\nBuilding a full certificate chain by checking each certificate in the chain against\nthe trusted root certificates");

        $ourChain = array();    // Build a list of all certificates in the chain, starting with the root cert
        foreach ($cas as $ca_hash => $ca_cert) {
            $output->writeln("Checking chain certificates against trusted cert: $ca_hash");
            foreach ($cs as $c_hash => $c_cert) {
                // Check that chain cert is signed (issued) by ca_cert
                if (1 === openssl_x509_verify($c_cert, $ca_cert)) {
                    $output->writeln("OK: Chain cert $c_hash is signed by trusted CA cert $ca_hash");
                    if (! empty($ourChain)) {
                        $output->writeln("ERROR: multiple certificates in the chain are signed a CA, multiple possible chains");
                    }
                    else {
                        $ourChain[]=$ca_cert;  // First add the CA cert to the chain
                        $ourChain[]=$c_cert;   // Next the chain cert we found
                    }
                }
                else {
                    $output->writeln("No match for Chain cert $c_hash");
                }
            }
        }

        if (empty($ourChain)) {
            $output->writeln("ERROR: No matching chain certificates found");
            return;
        }
        else {
            $output->writeln("OK: Matching chain certificate found");
        }

        $output->writeln("\nCompleting the chain using the remaining chain certificates");
        // Just try adding certs from the chain until we can no longer add anymore
        do {
            $changed=false;
            $last_cert = end($ourChain);
            foreach ($cs as $c_hash => $c_cert) {
                if ( in_array($c_cert, $ourChain) )
                    continue;   // Certificate already in our chain
                // Check if chain cert is signed (issued) by last_cert
                if (1 === openssl_x509_verify($c_cert, $last_cert)) {
                    $output->writeln("OK: Chain cert $c_hash is signed by last cert in chain");
                    $ourChain[]=$c_cert;
                    $changed=true;
                    break; // Break out of the foreach loop
                } else {
                    $output->writeln("No match for Chain cert $c_hash");
                }
            }
        } while ($changed);

        if (sizeof($ourChain) != sizeof($cs) + 1) {
            $output->writeln("ERROR: there are unused certificates in the chain");
        } else {
            $output->writeln("OK: all certificates in the chain are used");
        }
        $output->writeln("\nFinal certificate chain:");
        $nr=0;
        foreach ($ourChain as $cert) {
            $output->writeln("#$nr:");
            $output->writeln(Utils::X509toString(openssl_x509_parse($cert)));
            $out='';
            openssl_x509_export($cert, $out);
            $output->writeln($out);
            if ($nr==0) {
                if ( !in_array($cert, $cas) ) {
                    // Note: This check depends on how the certs are serialized to PEM format
                    // (e.g. 64 chars per line, CR vs CRLF)
                    $output->writeln("WARNING: Something may be wrong, the openssl PEM first certificate in the chain is not found in the trusted CAs");;
                }
            }
            else
            {
                // Same for chain cert
                if ( !in_array($cert, $cs) ) {
                    $output->writeln("WARNING: Something may be wrong, the openssl PEM PEM chain cert is found in the chain certificates");
                }
            }

            $nr++;
        }
    }

}
