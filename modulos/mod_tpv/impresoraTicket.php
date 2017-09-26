<?php
/**
 * This print-out shows how large the available font sizes are. It is included
 * separately due to the amount of text it prints.
 *
 * @author Michael Billington <michael.billington@gmail.com>
 */


require __DIR__ . './../../lib/escpos-php/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfile;
$profile = CapabilityProfile::load("simple",'CP437');
//~ $profile = CapabilityProfile::load("HOP-E801");
//~ $profile = CapabilityProfile::load("SP2000");

$connector = new FilePrintConnector("/dev/usb/lp0");

$printer = new Printer($connector,$profile);






