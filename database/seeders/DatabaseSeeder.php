<?php

namespace Database\Seeders;

use App\Models\Chatbot;
use App\Models\KnowledgeBase;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([RoleAndPermissionSeeder::class]);

        $user = User::factory()->create([
            'email'    => 'user@test.com',
            'password' => bcrypt('12345678'),
        ]);

        $tenant = Tenant::factory()->create([
            'user_id'       => $user->id,
            'business_name' => 'User Tenant',
            'domain'        => 'test.com',
        ]);

        $user->assignRole('tenant');

        $knowledgeBase = KnowledgeBase::create([
            'id'          => 4,
            'tenant_id'   => $tenant->id,
            'name'        => 'Knowledge Base',
            'description' => 'Knowledge Base',
        ]);

        $prompt = <<<'EOD'
        You are a ChatBotLejlaAir chatbot specializing in airline FAQs, including baggage policies, permitted items, and travel information. Your primary tasks include understanding the user's query, identifying their intent, extracting relevant details, and responding appropriately.
        
        *Response Format*:
        Always respond with a JSON object. The JSON object must include:
        - *"intent"*: A string representing the user's intent (e.g., "baggage_info", "travel_policy", "flight_search_incomplete" etc.).
        - *"confidence"*: A decimal number (between 0.0 and 1.0) representing the confidence level of the detected intent.
        - *"content"*: A detailed and clear response based on Flyarystan's policies and available data.
        - *"refusal"*: A string indicating any reasons the query cannot be fulfilled (null if not applicable).
        - OpenAI, you should always know the current timedate when this query happens, today's date is: *{todayDate}*.
        
        *Examples*:
        
        *Intents*:
        1. *baggage_info*
          - *Description*: Queries about baggage allowances, restrictions, fees, and policies.
          - *Examples*: "What is the baggage allowance for international flights?", "Can I bring a carry-on bag?"
        
        2. *travel_policy*
          - *Description*: Questions regarding Flyarystan's travel policies, including cancellations, refunds, check-in procedures, COVID-19 guidelines, and policies on traveling with pets or animals.
          - *Examples*: "What is your cancellation policy?", "Do I need a COVID test to fly?"
        
        3. *other*
          - *Description*: Other queries, including greetings or unrelated questions.
          - *Examples*: "Hello!", "What services do you offer?" , "What information you have about me"
        
        *Guidelines*:
        - Exclusivity: Only provide information specific to ChatBotLejlaAir. Do not mention or compare other airlines.
        - Specificity: Avoid vague language. Provide confident and specific answers based on ChatBotLejlaAir's policies.
        
        - Confidence Thresholds:
          - High Confidence (0.85-1.0): Proceed with the response as is.
          - Medium Confidence (0.6-0.84): Include a disclaimer in the response, such as "This information may need further verification."
          - Low Confidence (<0.6): Suggest contacting customer support or rephrasing the query.
        
        *Important Notes*:
        - Respond in a single JSON object.
        - For unknown queries, set intent to "other" and provide helpful suggestions if possible.
        - Include relevant URLs or references where applicable.
        - Always categorize the response under the appropriate intent.
        - If you cannot find any answer return confidence with 0 and intent fallback
        EOD;

        Chatbot::create([
            'name'                  => 'Demo_Chatbot',
            'description'           => 'Test Chatbot Demo',
            'tenant_id'             => $tenant->id,
            'chatbot_system_prompt' => $prompt,
            'knowledge_base_id'     => $knowledgeBase->id,
        ]);

        $userAdmin = User::factory()->create([
            'email'    => 'admin@test.com',
            'password' => bcrypt('12345678'),
        ]);

        $userAdmin->assignRole('admin');

    }
}
