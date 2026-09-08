<?php

namespace Database\Seeders;

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
      "id": "business",
      "nav": "Business",
      "enabled": true,
      "order": 20,
      "eyebrow": "WHAT WE DO",
      "title": "Built for\ncomplex projects.",
      "text": "Engineering thinking. On-site execution. A clear focus on the work that brings a project to life.",
      "items": [
        {
          "title": "Construction",
          "text": "Civil and structural works for buildings, from foundations through to finishing.",
          "detail": "Buildings · Civil works · Structures"
        },
        {
          "title": "Infrastructure",
          "text": "Civil infrastructure and the structural works that connect places and support growing communities.",
          "detail": "Civil infrastructure · Urban works"
        },
        {
          "title": "Project delivery",
          "text": "Planning, coordination and site management across the construction journey, through to handover.",
          "detail": "Planning · Execution · Handover"
        }
      ]
    },
    {
      "id": "capabilities",
      "nav": "Capabilities",
      "enabled": true,
      "order": 30,
      "eyebrow": "HOW WE BUILD",
      "title": "From plan\nto project.",
      "text": "Every stage has a purpose. Explore the construction journey, from the first plan to the final handover.",
      "items": [
        {
          "title": "Planning",
          "text": "Define the scope, sequence and requirements that guide the project."
        },
        {
          "title": "Engineering",
          "text": "Translate the project brief into coordinated technical requirements."
        },
        {
          "title": "Procurement",
          "text": "Coordinate materials and resources against the programme of work."
        },
        {
          "title": "Site mobilisation",
          "text": "Prepare the site and organise the people and resources required for execution."
        },
        {
          "title": "Foundation",
          "text": "Establish the base for the structure in accordance with the project design."
        },
        {
          "title": "Structure",
          "text": "Bring the structural framework to life, stage by stage."
        },
        {
          "title": "Construction",
          "text": "Coordinate civil works and the activities that turn a structure into a building."
        },
        {
          "title": "Quality & safety",
          "text": "Integrate quality requirements and safety considerations into the programme of work."
        },
        {
          "title": "Finishing",
          "text": "Coordinate the finishes and details required by the project specification."
        },
        {
          "title": "Handover",
          "text": "Bring together completion checks and documentation for project handover."
        }
      ]
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
      "id": "insights",
      "nav": "Insights",
      "enabled": true,
      "order": 70,
      "eyebrow": "INSIGHTS & KNOWLEDGE",
      "title": "A closer look\nat construction.",
      "text": "Project stories, construction knowledge and perspectives on the work behind the built environment.",
      "items": [
        {
          "title": "What is the company’s current focus?",
          "text": "Construction, infrastructure and project execution, with a current focus on the Tricity region."
        },
        {
          "title": "Is real estate an active business?",
          "text": "Development and real estate are part of the long-term vision. They are not presented as an active property-sales business."
        },
        {
          "title": "How can I discuss a project?",
          "text": "Use the enquiry form to share the project type, location and scope of your requirement."
        }
      ]
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
    }
}
