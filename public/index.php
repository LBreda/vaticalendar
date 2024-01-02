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

const UID_BASE = 'com.lbreda.vaticalendar.';

$dateIntervalRadius = $_ENV['DATE_INTERVAL_RADIUS'] ?? 5;
$baseDate = ($_GET['baseDate'] ?? null) ? Carbon::parse($_GET['baseDate']) : Carbon::now();
$firstDate = $baseDate->copy()->addYears(-$dateIntervalRadius)->startOfYear();
$lastDate = $baseDate->copy()->addYears($dateIntervalRadius)->endOfYear();

$events = [];

########################################################################################################################
# Fixed date events                                                                                                    #
########################################################################################################################
$fixedDateEvents = [
    '1 january'   => [
        'shortname' => 'holyMother',
        'name' => 'Madre di Dio',
    ],
    '6 january'   => [
        'shortname' => 'epiphany',
        'name' => 'Epifania',
    ],
    '11 february' => [
        'shortname' => 'lourdes',
        'name' => 'Beata Vergine Maria di Lourdes',
    ],
    '1 may'       => [
        'shortname' => 'stJosephWorker',
        'name' => 'San Giuseppe Lavoratore',
    ],
    '29 june'     => [
        'shortname' => 'stPeterPaulus',
        'name' => 'San Pietro e Paolo',
    ],
    '14 august'   => [
        'shortname' => 'summer1',
        'name' => 'Chiusura estiva',
    ],
    '15 august'   => [
        'shortname' => 'maryAssumption',
        'name' => 'Assunzione della Beata Vergine Maria',
    ],
    '16 august'   => [
        'shortname' => 'summer2',
        'name' => 'Chiusura estiva',
    ],
    '1 november'  => [
        'shortname' => 'allSaints',
        'name' => 'Tutti i santi',
    ],
    '2 november'  => [
        'shortname' => 'dead',
        'name' => 'Commemorazione dei defunti',
    ],
    '8 december'  => [
        'shortname' => 'immaculateConception',
        'name' => 'Immacolata Concezione',
    ],
    '25 december' => [
        'shortname' => 'christmas',
        'name' => 'Natale',
    ],
    '26 december' => [
        'shortname' => 'stSteph',
        'name' => 'Santo Stefano',
    ],
    '27 december' => [
        'shortname' => 'stJohn',
        'name' => 'San Giovanni Apostolo',
    ],
    '31 december' => [
        'shortname' => 'stSylvester',
        'name' => 'San Silvestro I, papa',
    ],
];
foreach ($fixedDateEvents as $date => $data) {
    $events[] = Event::create($data['name'])->uniqueIdentifier(UID_BASE . $data['shortname'])->startsAt(new DateTime("{$date} {$firstDate->year}"))->fullDay()->rrule(RRule::frequency(RecurrenceFrequency::yearly()));
}

########################################################################################################################
# Epiphany-bound and Easter-bound events                                                                               #
########################################################################################################################
for ($year = $firstDate->year; $year < $lastDate->year; $year++) {
    // Easter-bound
    $easterDate = Carbon::parse(easter_date($year))->setTimezone(new DateTimeZone('Europe/Vatican'));
    $events[] = Event::create('Domenica delle Palme')->uniqueIdentifier(UID_BASE . "palmSun_{$year}")->startsAt($easterDate->copy()->previous('Sunday'))->fullDay();
    $events[] = Event::create('Giovedì Santo')->uniqueIdentifier(UID_BASE . "goodThu_{$year}")->startsAt($easterDate->copy()->previous('Thursday'))->fullDay();
    $events[] = Event::create('Venerdì Santo')->uniqueIdentifier(UID_BASE . "goodFri_{$year}")->startsAt($easterDate->copy()->previous('Friday'))->fullDay();
    $events[] = Event::create('Sabato Santo')->uniqueIdentifier(UID_BASE . "goodSat_{$year}")->startsAt($easterDate->copy()->previous('Saturday'))->fullDay();
    $events[] = Event::create('Pasqua')->uniqueIdentifier(UID_BASE . "easter_{$year}")->startsAt($easterDate)->fullDay();
    $events[] = Event::create("Lunedì dell'Angelo")->uniqueIdentifier(UID_BASE . "easterMon_{$year}")->startsAt($easterDate->copy()->next('Monday'))->fullDay();
    $events[] = Event::create("Martedì in albis")->uniqueIdentifier(UID_BASE . "easterTue_{$year}")->startsAt($easterDate->copy()->next('Tuesday'))->fullDay();
    $events[] = Event::create("Ascensione")->uniqueIdentifier(UID_BASE . "ascension_{$year}")->startsAt($easterDate->copy()->addDays(39))->fullDay();
    $events[] = Event::create("Pentecoste")->uniqueIdentifier(UID_BASE . "pentecost_{$year}")->startsAt($easterDate->copy()->addDays(49))->fullDay();
    $events[] = Event::create("Santissima Trinità")->uniqueIdentifier(UID_BASE . "trinity_{$year}")->startsAt($easterDate->copy()->addDays(56))->fullDay();
    $events[] = Event::create("Corpo e Sangue di Cristo")->uniqueIdentifier(UID_BASE . "corpusDomini_{$year}")->startsAt($easterDate->copy()->addDays(60))->fullDay();
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
        $events[] = Event::create("Elezione di Sua Santità {$pope->name}")->uniqueIdentifier(UID_BASE . "pope_" . $pope->election->format('Ymd'))->startsAt($pope->election->copy()->addYear())->fullDay()->rrule($popeDatesFrequency);
        $events[] = Event::create("Onomastico di Sua Santità {$pope->name}")->uniqueIdentifier(UID_BASE . "pope_" . $pope->nameDay->format('Ymd'))->startsAt($pope->nameDay)->fullDay()->rrule($popeDatesFrequency);
    }
}

$calendar = Calendar::create('Festività in Vaticano')->refreshInterval(60)->event($events);
echo $calendar->get();
