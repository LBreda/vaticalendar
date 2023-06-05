<?php

include __DIR__ . "/../vendor/autoload.php";

use Carbon\Carbon;
use Dotenv\Dotenv;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Components\Timezone;
use Spatie\IcalendarGenerator\Enums\RecurrenceFrequency;
use Spatie\IcalendarGenerator\ValueObjects\RRule;

date_default_timezone_set('Europe/Vatican');
header('Content-Type: text/calendar');

$env = Dotenv::createImmutable(__DIR__ . '/..');
$env->safeLoad();

$dateIntervalRadius = $_ENV['DATE_INTERVAL_RADIUS'] ?? 5;
$baseDate = ($_GET['baseDate'] ?? null) ? Carbon::parse($_GET['baseDate']) : Carbon::now();
$firstDate = $baseDate->copy()->addYears(-$dateIntervalRadius)->startOfYear();
$lastDate = $baseDate->copy()->addYears($dateIntervalRadius)->endOfYear();

$events = [];

########################################################################################################################
# Fixed date events                                                                                                    #
########################################################################################################################
$fixedDateEvents = [
    '1 january'   => 'Madre di Dio',
    '6 january'   => 'Epifania',
    '11 february' => 'Beata Vergine Maria di Lourdes',
    '1 may'       => 'San Giuseppe Lavoratore',
    '29 june'     => 'San Pietro e Paolo',
    '14 august'   => 'Chiusura estiva',
    '15 august'   => 'Assunzione della Beata Vergine Maria',
    '16 august'   => 'Chiusura estiva',
    '1 november'  => 'Tutti i santi',
    '2 november'  => 'Commemorazione dei defunti',
    '8 december'  => 'Immacolata Concezione',
    '25 december' => 'Natale',
    '26 december' => 'Santo Stefano',
    '27 december' => 'San Giovanni Apostolo',
    '31 december' => 'San Silvestro I, papa',
];
foreach ($fixedDateEvents as $date => $name) {
    $events[] = Event::create($name)->startsAt(new DateTime("{$date} {$firstDate->year}"))->fullDay()->rrule(RRule::frequency(RecurrenceFrequency::yearly()));
}

########################################################################################################################
# Epiphany-bound and Easter-bound events                                                                               #
########################################################################################################################
for ($year = $firstDate->year; $year < $lastDate->year; $year++) {
    // Epiphany-bound
    $events[] = Event::create('Battesimo del Signore')->startsAt(Carbon::parse("6 january {$year}")->next('Monday'))->fullDay();

    // Easter-boubd
    $easterDate = Carbon::parse(easter_date($year))->setTimezone(new DateTimeZone('Europe/Vatican'));
    $events[] = Event::create('Domenica delle Palme')->startsAt($easterDate->copy()->previous('Sunday'))->fullDay();
    $events[] = Event::create('Giovedì Santo')->startsAt($easterDate->copy()->previous('Thursday'))->fullDay();
    $events[] = Event::create('Venerdì Santo')->startsAt($easterDate->copy()->previous('Friday'))->fullDay();
    $events[] = Event::create('Sabato Santo')->startsAt($easterDate->copy()->previous('Saturday'))->fullDay();
    $events[] = Event::create('Pasqua')->startsAt($easterDate)->fullDay();
    $events[] = Event::create("Lunedì dell'Angelo")->startsAt($easterDate->copy()->next('Monday'))->fullDay();
    $events[] = Event::create("Martedì in albis")->startsAt($easterDate->copy()->next('Tuesday'))->fullDay();
    $events[] = Event::create("Ascensione")->startsAt($easterDate->copy()->addDays(39))->fullDay();
    $events[] = Event::create("Pentecoste")->startsAt($easterDate->copy()->addDays(49))->fullDay();
    $events[] = Event::create("Santissima Trinità")->startsAt($easterDate->copy()->addDays(56))->fullDay();
    $events[] = Event::create("Corpo e Sangue di Cristo")->startsAt($easterDate->copy()->addDays(60))->fullDay();
}

########################################################################################################################
# Popes events                                                                                                         #
########################################################################################################################

// List of (supported) Popes
$popes = [
    (object)[
        'name'         => "Giovanni Paolo II",
        'election'     => Carbon::parse('19781016'),
        'endOfMandate' => Carbon::parse('20050402'),
        'nameDay'      => Carbon::parse('19781104'),
    ],
    (object)[
        'name'         => "Benedetto XVI",
        'election'     => Carbon::parse('20050419'),
        'endOfMandate' => Carbon::parse('20130228'),
        'nameDay'      => Carbon::parse('20060319'),
    ],
    (object)[
        'name'         => "Francesco",
        'election'     => Carbon::parse('20130313'),
        'endOfMandate' => null,
        'nameDay'      => Carbon::parse('20130423'),
    ],
];

// Saves the events for Popes in the interesting interval
foreach ($popes as $pope) {
    if ($pope->election->lt($firstDate) and ($pope->endOfMandate == null or $pope->endOfMandate->gt($lastDate))) {
        $popeDatesFrequency = $pope->endOfMandate ? RRule::frequency(RecurrenceFrequency::yearly())->until($pope->endOfMandate) : RRule::frequency(RecurrenceFrequency::yearly());
        $events[] = Event::create("Elezione di Sua Santità {$pope->name}")->startsAt($pope->election->copy()->addYear())->fullDay()->rrule($popeDatesFrequency);
        $events[] = Event::create("Onomastico di Sua Santità {$pope->name}")->startsAt($pope->nameDay)->fullDay()->rrule($popeDatesFrequency);
    }
}

$calendar = Calendar::create('Festività in Vaticano')->refreshInterval(60)->event($events);
echo $calendar->get();
