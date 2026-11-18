<?php

declare(strict_types=1);

namespace App;

/** Deterministic local substitute for an LLM; replaceable through the interface. */
final class MockContentGenerator implements ContentGeneratorInterface
{
    public function generateBrief(array $idea, array $business): array
    {
        return [
            'idea_id'=>$idea['id'],
            'status'=>'draft',
            'hook'=>'Deine Augen leisten jeden Tag viel – gönn ihnen zwischendurch eine kleine Pause.',
            'caption'=>'Lange am Bildschirm? Schau regelmäßig für einen Moment in die Ferne, blinzle bewusst und gönn dir kurze Pausen. Kleine Routinen können den Sehkomfort im Alltag unterstützen. Bei Fragen beraten wir dich gerne persönlich.',
            'production_plan'=>['Szene 1: Person am Schreibtisch vor dem Bildschirm.','Szene 2: Blick aus dem Fenster in die Ferne.','Szene 3: Drei Tipps direkt in die Kamera sprechen.','Szene 4: Freundlicher Abschluss im Geschäft.'],
            'text_overlays'=>['Bildschirm-Pause gefällig?','1. Blick in die Ferne','2. Bewusst blinzeln','3. Kurz aufstehen'],
            'cover_idea'=>'Freundliches Portrait am Arbeitsplatz mit dem Titel „3 kleine Augenpausen“.',
            'cta'=>'Vereinbare deine persönliche Sehberatung: '.$business['booking_url'],
            'media_ref'=>$idea['media_ref'],
            'channel_variants'=>[
                'Instagram'=>['format'=>'Reel mit Untertiteln und Texteinblendungen','text'=>'Kurze Pause für deine Augen 👀 Drei einfache Impulse für deinen Bildschirm-Alltag. Speichere dir den Tipp!'],
                'TikTok'=>['format'=>'Schneller Einstieg, direkte Ansprache und dynamische Schnitte','text'=>'Starrst du den ganzen Tag auf den Bildschirm? Stop kurz! Hier sind 3 kleine Pausen-Ideen für deinen Alltag.']
            ],
            'effort_minutes'=>['media_production'=>25,'channel_variants'=>10,'coordination'=>5,'approval'=>10]
        ];
    }
}
