<?php

namespace Database\Seeders;

use App\Models\ContentEntry;
use App\Models\Homepage;
use Illuminate\Database\Seeder;

class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        Homepage::firstOrCreate(['key' => 'main'], ['content' => json_decode(<<<'JSON'
{
  "hero": {
    "eyebrow": "ENGINEERING · CONSTRUCTION · INFRASTRUCTURE",
    "line_one": "BUILDING TODAY.",
    "line_two": "SHAPING",
    "line_three": "TOMORROW.",
    "description": "Engineering, construction and infrastructure execution for complex projects across the Tricity and beyond.",
    "primary_cta": "Start a project",
    "secondary_cta": "Explore our projects"
  },
  "contact": {
    "phone": "",
    "whatsapp": "",
    "email": "",
    "address": ""
  },
  "sections": [
    {
      "id": "about",
      "nav": "About",
      "enabled": true,
      "order": 10,
      "eyebrow": "THE COMPANY",
      "title": "Strong foundations.\nA bigger vision.",
      "text": "GOYAL ESTATE & DEVELOPERS PVT. LTD. is focused on construction, infrastructure and project execution. Rooted in the Tricity, our vision is to build the capabilities for new markets and future development opportunities.",
      "label": "Construction today. Possibilities tomorrow."
    },
    {
      "id": "projects",
      "nav": "Projects",
      "enabled": true,
      "order": 40,
      "eyebrow": "PROJECTS & EXECUTION",
      "title": "Our work\ndefines us.",
      "text": "Understand the scope, construction stages and project delivery behind the work.",
      "empty": "Project profiles will be shared here. For project-specific information, please send an enquiry.",
      "cta": "Discuss a similar project"
    },
    {
      "id": "presence",
      "nav": "",
      "enabled": true,
      "order": 50,
      "eyebrow": "OUR FOUNDATION. OUR DIRECTION.",
      "title": "Strong in the Tricity.\nReady for what’s next.",
      "text": "Our current focus is the Tricity region. Our long-term vision is to take our construction and infrastructure capabilities into new markets.",
      "label": "TRICITY",
      "future": "New markets are part of our future vision."
    },
    {
      "id": "careers",
      "nav": "Careers",
      "enabled": true,
      "order": 60,
      "eyebrow": "PEOPLE BUILD POSSIBILITIES",
      "title": "Build your\ncareer with us.",
      "text": "Explore opportunities in engineering, project management and construction operations.",
      "empty": "There are no published openings to display at present.",
      "cta": "Make a career enquiry"
    },
    {
      "id": "contact",
      "nav": "Contact",
      "enabled": true,
      "order": 80,
      "eyebrow": "START A CONVERSATION",
      "title": "Let’s build\nsomething significant.",
      "text": "Tell us what you have in mind. Share the location, project type and a little about your requirements.",
      "cta": "Send project enquiry"
    }
  ],
  "seo": {
    "title": "GOYAL ESTATE & DEVELOPERS PVT. LTD. | Engineering, Construction & Infrastructure",
    "description": "Construction, infrastructure and project execution with a strong Tricity foundation. Explore GOYAL ESTATE & DEVELOPERS PVT. LTD. and discuss your project."
  }
}
JSON, true, 512, JSON_THROW_ON_ERROR)]);
        $entry = ContentEntry::firstOrCreate(['type' => 'homepage', 'slug' => 'home'], ['title' => 'Homepage']);
        if (! $entry->revisions()->exists()) {
            $revision = $entry->revisions()->create(['version' => 1, 'payload' => Homepage::main()->content]);
            $entry->update(['published_revision_id' => $revision->id, 'published_at' => now(), 'status' => 'published']);
        }

    }
}
