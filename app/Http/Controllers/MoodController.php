<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MoodController extends Controller
{
    const MOODS = [
        'joie'    => ['emoji' => '😊', 'label' => 'en joie',    'color' => '#fbbf24'],
        'colere'  => ['emoji' => '😡', 'label' => 'en colère',  'color' => '#ef4444'],
        'danse'   => ['emoji' => '💃', 'label' => 'en train de danser', 'color' => '#a78bfa'],
        'furieux' => ['emoji' => '🤬', 'label' => 'furieux(se)','color' => '#dc2626'],
        'triste'  => ['emoji' => '😢', 'label' => 'triste',     'color' => '#60a5fa'],
        'musique' => ['emoji' => '🎵', 'label' => 'en mode musique', 'color' => '#34d399'],
    ];

    public function update(Request $request)
    {
        $request->validate(['mood' => 'nullable|string']);
        $user = Auth::user();
        $mood = $request->mood;

        $user->update(['mood' => $mood]);

        if ($mood && isset(self::MOODS[$mood])) {
            $info = self::MOODS[$mood];
            // Crée une activité visible dans le feed
            Activity::create([
                'user_id' => $user->id,
                'type'    => 'mood',
                'data'    => [
                    'mood'  => $mood,
                    'emoji' => $info['emoji'],
                    'label' => $info['label'],
                    'color' => $info['color'],
                ],
            ]);
        }

        return response()->json(['success' => true, 'mood' => $mood]);
    }

    public function moods()
    {
        return response()->json(self::MOODS);
    }
}