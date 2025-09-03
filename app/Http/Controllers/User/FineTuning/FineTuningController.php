<?php

namespace App\Http\Controllers\User\FineTuning;

use App\Http\Controllers\Controller;
use App\Jobs\FineTuneChatbot;
use App\Jobs\ProcessEmbeddings;
use App\Models\Chatbot;
use Illuminate\Http\Request;

class FineTuningController extends Controller
{
    // not supported yet
    // public function start_fine_tuning(Request $request)
    // {
    //     $chatbot = Chatbot::where('tenant_id', $this->authUser()->tenant->id)->firstOrfail();

    //     FineTuneChatbot::dispatch($chatbot->id, $request->media_id);
    //     ProcessEmbeddings::dispatch($chatbot->knowledge_base, $request->media_id);

    //     return $this->success('Fine Tuning started');

    // }
}
