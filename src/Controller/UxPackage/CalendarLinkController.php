<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\UxPackage;

use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\CalendarLink\CalendarEvent;
use Symfony\UX\CalendarLink\CalendarRecurrence;
use Symfony\UX\CalendarLink\CalendarReminder;

class CalendarLinkController extends AbstractController
{
    #[Route('/calendar-link', name: 'app_calendar_link')]
    public function __invoke(UxPackageRepository $packageRepository): Response
    {
        $package = $packageRepository->find('calendar-link');

        $timezone = new \DateTimeZone('Europe/Warsaw');
        $symfonyCon = new CalendarEvent(
            title: 'SymfonyCon Warsaw 2026',
            start: new \DateTimeImmutable('2026-11-26 09:00', $timezone),
            end: new \DateTimeImmutable('2026-11-27 18:00', $timezone),
            description: 'Share your best practices, experience and knowledge with Symfony.',
            location: 'Hilton Warsaw Hotel and Convention Centre, Grzybowska 63, 00-844, Warszawa, Poland',
        );

        $timezone = new \DateTimeZone('Europe/Paris');
        $victoryDay = new CalendarEvent(
            title: 'Victory in Europe Day',
            start: new \DateTimeImmutable('2025-05-08', $timezone),
            end: new \DateTimeImmutable('2025-05-09', $timezone),
            description: 'Public holiday in France marking the end of World War II in Europe (1945).',
            allDay: true,
            recurrence: CalendarRecurrence::yearly(),
        );

        $timezone = new \DateTimeZone('Europe/Paris');
        $bloodDonation = new CalendarEvent(
            title: 'Blood donation',
            start: new \DateTimeImmutable('2025-06-12 14:00', $timezone),
            end: new \DateTimeImmutable('2025-06-12 15:00', $timezone),
            location: 'EFS Lyon Part-Dieu, 74 rue de la Villette, 69003 Lyon',
            reminders: [
                CalendarReminder::before(days: 1, description: 'Eat and drink well.'),
                CalendarReminder::before(hours: 2, description: 'Time to go!'),
            ],
        );

        return $this->render('ux_packages/calendar_link.html.twig', [
            'package' => $package,
            'symfonyCon' => $symfonyCon,
            'victoryDay' => $victoryDay,
            'bloodDonation' => $bloodDonation,
        ]);
    }
}
