<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiController extends Controller
{
    private string $aiBase = 'http://127.0.0.1:5001';

    private function aiRequest(string $path): array
    {
        try {
            $response = Http::timeout(5)->get($this->aiBase . $path);
            return $response->json() ?? ['error' => 'Empty response'];
        } catch (\Exception $e) {
            return ['error' => 'AI server offline. Start it with: dev or serve'];
        }
    }

    public function index()
    {
        $stats = $this->aiRequest('/stats');
        $suggestions = $this->aiRequest('/suggestions');
        $recentInsights = AiInsight::with('task')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('ai.index', compact('stats', 'suggestions', 'recentInsights'));
    }

    public function predict(Request $request)
    {
        $request->validate(['text' => 'required|string|max:255']);

        $text = $request->input('text');
        $result = $this->aiRequest('/predict?text=' . urlencode($text));

        if (!isset($result['error'])) {
            AiInsight::create([
                'type' => 'prediction',
                'input_text' => $text,
                'prediction' => $result['prediction'] ?? null,
                'confidence' => $result['confidence'] ?? null,
                'completion_likelihood' => $result['completion_likelihood'] ?? null,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->route('ai.index')->with('prediction', $result)->with('predicted_text', $text);
    }

    public function train()
    {
        $result = $this->aiRequest('/train');
        return redirect()->route('ai.index')->with('success', 'Model retrained on ' . ($result['tasks_used'] ?? 0) . ' tasks.');
    }

    public function health()
    {
        $result = $this->aiRequest('/health');
        return response()->json($result);
    }
}