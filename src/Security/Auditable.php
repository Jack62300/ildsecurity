<?php
// src/Security/Auditable.php
namespace App\Security;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class Auditable {}
