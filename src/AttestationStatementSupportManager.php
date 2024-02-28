<?php

namespace Surfnet\Webauthn;

use Cose\Algorithm\Manager;
use Webauthn\AttestationStatement\AttestationStatementSupportManager as BaseAttestationStatementSupportManager;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;

class AttestationStatementSupportManager extends BaseAttestationStatementSupportManager
{
    public function __construct(

    )
    {
        parent::__construct(

        );
        $attestationStatementSupports->add(new PackedAttestationStatementSupport(Manager::create()));
    }
};