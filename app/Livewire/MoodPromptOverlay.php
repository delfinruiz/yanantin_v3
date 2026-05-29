<?php

namespace App\Livewire;

use App\Models\Mood;
use App\Models\User;
use App\Services\AiMessageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class MoodPromptOverlay extends Component
{
    public bool $showOverlay = false;

    public bool $processing = false;

    public ?string $selectedMood = null;

    public function mount(): void
    {
        $this->checkMoodStatus();
    }

    public function checkMoodStatus(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->is_internal || $user->hasRole('super_admin') || ! Schema::hasTable('moods')) {
            $this->showOverlay = false;

            return;
        }

        $hasMood = Mood::where('user_id', $user->id)
            ->whereDate('date', today())
            ->exists();

        $this->showOverlay = ! $hasMood;
    }

    public function selectMood(string $mood): void
    {
        if (! in_array($mood, Mood::CODES)) {
            return;
        }

        $this->selectedMood = $mood;
        $this->processing = true;

        $user = Auth::user();

        if (! $user) {
            return;
        }

        $score = Mood::scoreFor($mood);

        $moodEntry = Mood::create([
            'user_id' => $user->id,
            'date' => today(),
            'mood' => $mood,
            'score' => $score,
        ]);

        $service = app(AiMessageService::class);
        $message = $service->generateDailyMessage($user, $mood, $score);

        $moodEntry->update([
            'message' => $message,
            'message_model' => $service->model ?? config('services.openai.model'),
            'message_generated_at' => now(),
        ]);

        if ($user->is_internal && $user->email) {
            // No enviar email por ahora
        }

        $this->dispatch('mood-saved');
        $this->dispatch('daily-mood-updated');
        $this->showOverlay = false;
        $this->processing = false;
    }

    public function dismiss(): void
    {
        $this->showOverlay = false;
    }

    public function render()
    {
        return view('livewire.mood-prompt-overlay');
    }
}
