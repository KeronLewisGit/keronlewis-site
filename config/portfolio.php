<?php

/*
|--------------------------------------------------------------------------
| Portfolio content
|--------------------------------------------------------------------------
|
| Everything the site says about you lives in this file. The home page,
| the web résumé, the PDF, the vCard and /resume.json all read from here,
| so a change made once shows up everywhere.
|
*/

return [

    'profile' => [
        'name' => 'Keron Lewis',
        'title' => 'Full-Stack Web Developer',
        'location' => 'Port of Spain, Trinidad & Tobago',
        'city' => 'Port of Spain',
        'country_code' => 'TT',
        'timezone' => 'America/Port_of_Spain',
        'email' => 'keronlewis@live.com',
        'phone' => '+1 (868) 275-3268',
        'phone_e164' => '+18682753268',
        'availability' => 'Open to full-time roles and contracts',
        'remote' => 'Open to remote',

        /*
        | What search engines show: the browser-tab title and the snippet under it.
        | Keep titles under about 60 characters and descriptions under about 155.
        */
        'seo' => [
            'home_title' => 'Web Development in Trinidad & Tobago | Keron Lewis',
            'home_description' => 'Web development in Trinidad and Tobago by Keron Lewis, a web developer in Port of Spain: business websites, WordPress and WooCommerce stores, and web apps.',
            'resume_title' => 'Keron Lewis Résumé (CV) | Full-Stack PHP & WordPress Developer',
            'resume_description' => 'Résumé of Keron Lewis, full-stack web developer in Trinidad and Tobago: PHP, MySQL, React, WordPress and API integration work since 2016. PDF download.',
        ],

        'tagline' => 'Websites, web apps, and the plumbing behind them.',
        'tagline_mark' => 'plumbing',

        'lead' => 'I build websites, WordPress and WooCommerce stores, and custom web apps for businesses in Trinidad & Tobago and around the Caribbean, and have done since 2016. Day to day that means PHP and MySQL on the server, JavaScript and React in the browser, and a lot of REST APIs in between.',

        'summary' => 'Full-stack web developer based in Port of Spain, building for the web since 2016. Most of my work is PHP and MySQL applications, WordPress and WooCommerce builds, and integrations between business systems over REST APIs and webhooks. I usually own a piece of work from requirements through to release, then document it and train the people who will use it. Currently studying for an MSc in Data Science at UWI.',

        'about' => [
            "I'm a developer in Port of Spain. I started out in 2016 building and maintaining client websites, and over the years the work moved further back into the stack: PHP applications, MySQL reporting, payment gateways, and getting one system to hand data to another without somebody retyping it.",
            'A lot of what I build is unglamorous. A quote-request form that validates properly. A WooCommerce checkout that takes WiPay. A report that someone used to put together by hand in a spreadsheet every month. I like that kind of work, and I like being the person who sees it through from the first conversation to the release.',
            "Most projects I hand over come with documentation and a walkthrough for the people who'll be using them.",
            "I'm part-way through an MSc in Data Science at UWI St. Augustine, which is why more of my recent work involves reporting and cleaning up data.",
        ],

        'now' => [
            ['label' => 'Working on', 'value' => 'PHP business applications at A.V. Knowles & Co.'],
            ['label' => 'Studying', 'value' => 'MSc Data Science, UWI St. Augustine'],
            ['label' => 'Studio', 'value' => 'Code Canvas Consultants'],
        ],

        'links' => [
            'github' => ['label' => 'GitHub', 'handle' => 'github.com/KeronLewisGit', 'url' => 'https://github.com/KeronLewisGit'],
            'linkedin' => ['label' => 'LinkedIn', 'handle' => 'linkedin.com/in/keronlewis', 'url' => 'https://www.linkedin.com/in/keronlewis'],
            'studio' => ['label' => 'Code Canvas', 'handle' => 'codecanvastt.com', 'url' => 'https://codecanvastt.com'],
        ],
    ],

    /*
    | The services on offer. Each one is a card on the home page under
    | "Web development services" and has its own page at /services/{slug},
    | which is what search engines land people on.
    |
    |   'title' / 'text'  the card on the home page
    |   'page.heading'    the page's main heading
    |   'page.seo_title'  the browser-tab title (under about 60 characters)
    |   'page.summary'    the line under the heading and the search snippet
    |                     (under about 155 characters)
    |   'page.sections'   each with a 'heading', then 'body' (paragraphs)
    |                     and/or 'points' (a bulleted list)
    |   'page.projects'   slugs from 'projects' below, shown as related work
    */
    'services' => [
        [
            'slug' => 'website-development',
            'title' => 'Website development',
            'text' => 'Company websites, product catalogues and portfolio sites for businesses in Trinidad & Tobago, taken from the first conversation through to launch and support.',
            'page' => [
                'heading' => 'Website development in Trinidad & Tobago',
                'seo_title' => 'Website Development in Trinidad & Tobago | Keron Lewis',
                'summary' => 'I build websites for businesses in Trinidad and Tobago: company sites, product catalogues and portfolios that work on any screen and come with training.',
                'sections' => [
                    [
                        'heading' => 'What I build',
                        'body' => [
                            "Most of the websites I build are for small and mid-sized businesses and organisations in Trinidad & Tobago and around the Caribbean. Recent ones include a garment manufacturer's product catalogue, a national nonprofit's site with a resources section, a private chef's portfolio and an events company's site.",
                        ],
                        'points' => [
                            'Company and brand websites.',
                            'Product catalogues organised by product line.',
                            'Portfolio and gallery sites.',
                            'Websites for nonprofits and community organisations.',
                        ],
                    ],
                    [
                        'heading' => 'How a project runs',
                        'body' => [
                            'I handle a website project from the first conversation to launch and the support after it. That includes the parts around the site itself: hosting, DNS, SSL and email set-up, and fixing them when something goes wrong.',
                            "Every website is handed over with documentation and a walkthrough for the people who'll be updating it.",
                        ],
                    ],
                    [
                        'heading' => 'Where I work',
                        'body' => [
                            "I'm a web developer based in Port of Spain and work with clients on site in Trinidad or remotely. Client projects run through my studio, Code Canvas Consultants.",
                        ],
                    ],
                ],
                'projects' => ['cruz-garments', 'rape-crisis-society', 'chef-brigette', 'for-the-culture', 'screen-stars', 'chez-nous-de-rubies'],
            ],
        ],
        [
            'slug' => 'wordpress-development',
            'title' => 'WordPress development',
            'text' => 'WordPress, Elementor and WooCommerce builds: company sites, online stores and course platforms, with checkout through Caribbean payment gateways such as WiPay.',
            'page' => [
                'heading' => 'WordPress development in Trinidad & Tobago',
                'seo_title' => 'WordPress Developer in Trinidad & Tobago | Keron Lewis',
                'summary' => 'WordPress and WooCommerce development in Trinidad and Tobago: company websites, online stores with WiPay checkout, and course platforms.',
                'sections' => [
                    [
                        'heading' => 'What I build',
                        'body' => [
                            "I've been a WordPress developer since 2016, and the live sites in my portfolio run on it.",
                        ],
                        'points' => [
                            'Company websites on WordPress and Elementor.',
                            'Online stores on WooCommerce, with checkout through Caribbean payment gateways such as WiPay.',
                            'Course platforms with enrolment, class scheduling and certificates.',
                            'Forms and internal workflows with Gravity Forms.',
                        ],
                    ],
                    [
                        'heading' => 'When the plugins run out',
                        'body' => [
                            "Where configuration isn't enough I write PHP, JavaScript and CSS, or connect WordPress to other systems with REST APIs and webhooks. Two examples are the paid SEO audit tool on my studio's site and the drinks calculator I wrote for Mixers Anonymous.",
                        ],
                    ],
                    [
                        'heading' => 'Looking after a site',
                        'body' => [
                            'I also sort out the things that go wrong on WordPress sites: plugin conflicts, payment problems, hosting, DNS, Cloudflare, SSL and email delivery. I deal with clients directly and explain the options in plain language.',
                        ],
                    ],
                ],
                'projects' => ['code-canvas', 'mixers-anonymous', 'mixers-training', 'cruz-garments', 'chef-brigette', 'screen-stars'],
            ],
        ],
        [
            'slug' => 'web-app-development',
            'title' => 'Web app development',
            'text' => 'Custom web applications in PHP, MySQL, JavaScript and React: internal tools, multi-step forms, and the reports a business runs on.',
            'page' => [
                'heading' => 'Web app development in Trinidad & Tobago',
                'seo_title' => 'Web App Development in Trinidad & Tobago | Keron Lewis',
                'summary' => 'Custom web app development in Trinidad and Tobago: business applications, internal tools and reporting built in PHP, MySQL, JavaScript and React.',
                'sections' => [
                    [
                        'heading' => 'What I build',
                        'body' => [
                            "A web app is software you open in a browser, on a phone or a computer, with nothing to install. I build them for the jobs an off-the-shelf product doesn't cover.",
                        ],
                        'points' => [
                            'Business applications in PHP and MySQL.',
                            'Internal tools and multi-step forms with validation, notifications and reporting.',
                            'MySQL reports that replace a spreadsheet someone puts together by hand.',
                            'Front ends in JavaScript and React.',
                        ],
                    ],
                    [
                        'heading' => 'Experience behind it',
                        'body' => [
                            "Since 2021 I've developed and maintained production business applications in PHP, Phalcon and MySQL for A.V. Knowles & Co. At Label House Group I built a multi-step quote-request application with validation, prefill logic, notifications and reporting.",
                            'This site is a small web app too: I designed and built it on Laravel.',
                        ],
                    ],
                    [
                        'heading' => 'From requirements to release',
                        'body' => [
                            'I usually own a piece of app development from requirements through to release, test it properly before it ships, then document it and train the people who will use it.',
                        ],
                    ],
                ],
                'projects' => ['code-canvas', 'mixers-anonymous'],
            ],
        ],
        [
            'slug' => 'integrations-automation',
            'title' => 'Integrations and automation',
            'text' => 'Getting business systems to share data using REST APIs, webhooks, Make, Zapier and n8n, so nobody has to retype it.',
            'page' => [
                'heading' => 'Integrations and automation for businesses in Trinidad & Tobago',
                'seo_title' => 'API Integration & Automation in Trinidad & Tobago | Keron Lewis',
                'summary' => 'API integrations and workflow automation in Trinidad and Tobago: REST APIs, webhooks, payment gateways, Make, Zapier and n8n.',
                'sections' => [
                    [
                        'heading' => 'What I build',
                        'points' => [
                            'Integrations between business systems over REST APIs and webhooks.',
                            'Workflow automation with Make, Zapier and n8n.',
                            'Payment gateway integration, including WiPay for WooCommerce stores.',
                            'Internal forms that feed straight into the tools a team already uses.',
                        ],
                    ],
                    [
                        'heading' => 'Examples',
                        'body' => [
                            'At Label House Group I ran an order-to-invoice automation pilot that cut cycle time by about 35%, and scoped a two-way integration between ClickUp and the Radius ERP.',
                            'For my own studio I built a Python and Playwright pipeline that extracted and structured more than 2,100 knowledge-base articles.',
                        ],
                    ],
                    [
                        'heading' => 'How I approach it',
                        'body' => [
                            'Before building anything I work out the data flow and the error handling, and compare the options: an automation platform such as Make or n8n, or a custom script.',
                            'When an integration fails in production, I trace the API, permission or data-flow problem back to its root cause.',
                        ],
                    ],
                ],
                'projects' => ['code-canvas'],
            ],
        ],
    ],

    /*
    | Where contact-form messages are emailed. Every message is also saved
    | to the contact_messages table, so nothing is lost if mail is down.
    */
    'contact_to' => env('CONTACT_TO', 'keronlewis@live.com'),

    'contact_topics' => [
        'role' => 'A role or contract',
        'project' => 'A site, store or integration',
        'other' => 'Something else',
    ],

    /*
    |--------------------------------------------------------------------------
    | Selected work
    |--------------------------------------------------------------------------
    | Sites built for clients and for my own businesses.
    | 'groups' drive the filter chips on the home page.
    | Screenshots live in public/img/work/{slug}.webp and are refreshed with
    | `php artisan portfolio:screenshots`.
    |
    | Two optional blocks on a project:
    |
    | 'case_study' gives it a page at /work/{slug}, linked from its card:
    |     'title'    the page heading
    |     'summary'  one or two sentences under the heading; also the search
    |                snippet, so keep it under about 155 characters
    |     'sections' each with a 'heading', then 'body' (paragraphs) and/or
    |                'points' (a bulleted list)
    |
    | 'testimonial' shows a client's words on the card and the case study:
    |     'testimonial' => [
    |         'quote' => 'What they said, word for word.',
    |         'name' => 'Their name',
    |         'role' => 'Their job title, Company',   // optional
    |     ],
    | Only add one the client has actually given you and agreed to publish.
    */
    'project_groups' => [
        'tools' => 'Custom tools',
        'hospitality' => 'Hospitality & events',
        'community' => 'Community & care',
        'manufacturing' => 'Manufacturing',
    ],

    'projects' => [
        [
            'slug' => 'code-canvas',
            'name' => 'Code Canvas Consultants',
            'category' => 'Studio',
            'groups' => ['tools'],
            'url' => 'https://codecanvastt.com/',
            'summary' => "My own studio's site. It includes an SEO audit tool I built, with payment handled through WiPay.",
            'stack' => ['WordPress', 'Elementor', 'Custom tool', 'WiPay'],
            'case_study' => [
                'title' => 'A paid SEO audit, sold from the studio home page',
                'summary' => "An SEO audit tool on my studio's site. Enter a web address, pay by card through WiPay, and a PDF report arrives by email.",
                'sections' => [
                    [
                        'heading' => 'What it is',
                        'body' => [
                            'Code Canvas is my own web studio. Its home page carries an SEO audit tool that I built, so a visitor can check their own site and buy a full report without having to contact me first.',
                        ],
                    ],
                    [
                        'heading' => 'How it works',
                        'points' => [
                            'The visitor enters their website address and the tool analyses the site.',
                            'The full audit is a paid report: more than 50 checks, including technical SEO, with recommendations for what to fix.',
                            'Payment is by credit or debit card through WiPay, a Caribbean payment gateway.',
                            'The report is produced as a PDF and emailed to the visitor straight away.',
                        ],
                    ],
                    [
                        'heading' => 'My part',
                        'body' => [
                            'I designed and built the site on WordPress and Elementor, then wrote the audit tool and its WiPay payment step as custom work on top.',
                        ],
                    ],
                ],
            ],
        ],
        [
            'slug' => 'mixers-anonymous',
            'name' => 'Mixers Anonymous TT',
            'category' => 'Hospitality',
            'groups' => ['tools', 'hospitality'],
            'url' => 'https://mixersanontt.com/',
            'summary' => 'A bar-service company I co-own. I built the site and wrote an alcohol calculator for it so clients can work out how much to buy for an event.',
            'stack' => ['WordPress', 'Elementor', 'Custom JS'],
            'case_study' => [
                'title' => 'A drinks calculator for planning an event',
                'summary' => 'Mixers Anonymous is a bar-service company I co-own. I built its site and a calculator that tells clients how much alcohol to buy for an event.',
                'sections' => [
                    [
                        'heading' => 'What it is',
                        'body' => [
                            'Mixers Anonymous TT is a bar-service company I co-own. I built the site, and wrote an alcohol calculator for its home page so clients can work out how much to buy for an event.',
                        ],
                    ],
                    [
                        'heading' => 'What the client enters',
                        'points' => [
                            'How many guests are coming, and what share of them will drink.',
                            'How many hours drinks will be served.',
                            'How the drinkers split between beer, wine and mixed drinks. The three shares have to add up to 100%.',
                        ],
                    ],
                    [
                        'heading' => 'What they get back',
                        'body' => [
                            'The cases of beer, bottles of wine and bottles of liquor to buy, an average cost for each, and an estimated total in Trinidad and Tobago dollars.',
                        ],
                    ],
                    [
                        'heading' => 'My part',
                        'body' => [
                            'I built the site on WordPress and Elementor and wrote the calculator in JavaScript.',
                        ],
                    ],
                ],
            ],
        ],
        [
            'slug' => 'mixers-training',
            'name' => 'Mixers Training Platform',
            'category' => 'E-learning',
            'groups' => ['hospitality'],
            'url' => 'https://courses.mixersanontt.com/',
            'summary' => 'The training side of Mixers Anonymous: mixology courses online, with enrolment, class scheduling, and certificates when students finish.',
            'stack' => ['WordPress', 'LMS', 'Booking'],
        ],
        [
            'slug' => 'rape-crisis-society',
            'name' => 'Rape Crisis Society TT',
            'category' => 'Nonprofit',
            'groups' => ['community'],
            'url' => 'https://rapecrisissocietytt.com/',
            'summary' => 'Website for a national nonprofit that supports survivors of sexual violence, including a resources section.',
            'stack' => ['WordPress', 'Elementor', 'Resources'],
        ],
        [
            'slug' => 'chez-nous-de-rubies',
            'name' => 'Chez Nous De Rubies',
            'category' => 'Healthcare',
            'groups' => ['community'],
            'url' => 'https://cheznousderubies.com/',
            'summary' => 'Brand site for a senior-care service.',
            'stack' => ['WordPress', 'Elementor'],
        ],
        [
            'slug' => 'chef-brigette',
            'name' => 'Chef Brigette',
            'category' => 'Personal brand',
            'groups' => ['hospitality'],
            'url' => 'https://chefbrigette.com/',
            'summary' => 'Portfolio site for an award-winning Caribbean private chef, built around a photo gallery.',
            'stack' => ['WordPress', 'Elementor', 'Gallery'],
        ],
        [
            'slug' => 'for-the-culture',
            'name' => 'For The Culture',
            'category' => 'Events',
            'groups' => ['hospitality'],
            'url' => 'https://fortheculturexperience.com/',
            'summary' => 'Site for a company that runs cultural events and festivals around the region.',
            'stack' => ['WordPress', 'Slider Revolution'],
        ],
        [
            'slug' => 'cruz-garments',
            'name' => 'Cruz Garments Ltd.',
            'category' => 'Manufacturing',
            'groups' => ['manufacturing'],
            'url' => 'https://cruzgarmentstt.com/',
            'summary' => 'Product catalogue for an apparel manufacturer, organised by product line.',
            'stack' => ['WordPress', 'Elementor', 'Catalogue'],
        ],
        [
            'slug' => 'screen-stars',
            'name' => 'Screen Stars Ltd.',
            'category' => 'Manufacturing',
            'groups' => ['manufacturing'],
            'url' => 'https://screenstarsltd.com/',
            'summary' => "Site for a screen-printing and embroidery shop that's been operating since 1989.",
            'stack' => ['WordPress', 'Elementor'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Experience
    |--------------------------------------------------------------------------
    | 'type' is the employment type shown beside the dates (null to leave it out).
    | 'ended' (optional) schedules a role's end: from the 'from' date onward its
    | other values replace the ones above, so the site updates itself that day.
    | 'start' / 'end' are YYYY-MM and position the bars on the résumé's career
    | chart ('end' => null means current). 'period' is the text shown to
    | readers. 'stack' names must match entries under 'skills' for the
    | résumé's skill filter to pick them up.
    */
    'experience' => [
        [
            'id' => 'label-house',
            'role' => 'Software Applications Specialist',
            'org' => 'Label House Group Ltd.',
            'org_note' => 'Caribbean packaging and label manufacturer, 20+ markets',
            'period' => 'Apr 2025 – Present',
            'type' => 'Full-time',
            'start' => '2025-04',
            'end' => null,
            'summary' => 'I build the internal web forms and workflow automation the departments run on, using ClickUp, Make, REST APIs and webhooks. I scoped a two-way integration between ClickUp and the Radius ERP, built a multi-step quote-request application, and ran an order-to-invoice automation pilot that cut cycle time by about 35%.',
            'bullets' => [
                'Design and deliver internal web applications, workflows and integrations with WordPress and Gravity Forms, REST APIs, webhooks, ClickUp and Make, from requirements through rollout and documentation.',
                'Scoped a two-way ClickUp–Radius ERP integration against the Radius XLink REST API. Compared Make, n8n, Zapier and custom scripts, and worked out the data flow and error handling before any build started.',
                'Built a multi-step RFQ application with validation, prefill logic, notifications, reporting and reCAPTCHA.',
                'Led an order-to-invoice automation pilot that reduced cycle time by about 35%.',
                'Write the technical standards, SOPs and training material the team works from, and trace API, permission and data-flow failures in production back to root cause.',
            ],
            'stack' => ['REST APIs', 'Webhooks', 'Make', 'n8n', 'Zapier', 'WordPress', 'Gravity Forms', 'JavaScript', 'PHP'],
            'ended' => [
                'from' => '2026-11-16',
                'period' => 'Apr 2025 – Nov 2026',
                'end' => '2026-11',
                'summary' => 'I built the internal web forms and workflow automation the departments ran on, using ClickUp, Make, REST APIs and webhooks. I scoped a two-way integration between ClickUp and the Radius ERP, built a multi-step quote-request application, and ran an order-to-invoice automation pilot that cut cycle time by about 35%.',
                'bullets' => [
                    'Designed and delivered internal web applications, workflows and integrations with WordPress and Gravity Forms, REST APIs, webhooks, ClickUp and Make, from requirements through rollout and documentation.',
                    'Scoped a two-way ClickUp–Radius ERP integration against the Radius XLink REST API. Compared Make, n8n, Zapier and custom scripts, and worked out the data flow and error handling before any build started.',
                    'Built a multi-step RFQ application with validation, prefill logic, notifications, reporting and reCAPTCHA.',
                    'Led an order-to-invoice automation pilot that reduced cycle time by about 35%.',
                    'Wrote the technical standards, SOPs and training material the team worked from, and traced API, permission and data-flow failures in production back to root cause.',
                ],
            ],
        ],
        [
            'id' => 'code-canvas',
            'role' => 'Founder & Full-Stack Web Developer',
            'org' => 'Code Canvas Consultants Ltd.',
            'org_note' => 'My own web studio',
            'period' => 'Jan 2024 – Present',
            'type' => 'Part-time',
            'start' => '2024-01',
            'end' => null,
            'summary' => 'I handle each client project from the first conversation to launch and support: WordPress, Elementor and WooCommerce builds, custom PHP and JavaScript where the plugins run out, and the hosting, DNS and email problems that come with looking after client sites.',
            'bullets' => [
                'Run client projects from discovery to launch and ongoing support, across e-commerce, education, hospitality and professional services.',
                "Configure and extend WordPress themes, plugins, forms and payment flows. Where configuration isn't enough I write PHP, JavaScript and CSS, or connect things with REST APIs, webhooks, Make and Zapier.",
                'Troubleshoot hosting, DNS, Cloudflare, SSL, email delivery, plugin conflicts and payment issues directly with clients, and explain the options in plain language.',
                'Built a Python and Playwright pipeline that extracted and structured 2,100+ knowledge-base articles across 33 sections.',
                'Write documentation, training and handover material for every project.',
            ],
            'stack' => ['WordPress', 'Elementor', 'WooCommerce', 'PHP', 'JavaScript', 'CSS3', 'REST APIs', 'Webhooks', 'Make', 'Zapier', 'Python', 'Playwright', 'Cloudflare'],
        ],
        [
            'id' => 'av-knowles',
            'role' => 'Application Developer',
            'org' => 'A.V. Knowles & Co.',
            'org_note' => null,
            'period' => 'May 2021 – Present',
            'type' => 'Consultant, hourly',
            'start' => '2021-05',
            'end' => null,
            'summary' => 'I develop and maintain business applications in PHP, Phalcon and MySQL on Linux, and write the heavier MySQL reports. Working closely with QA on testing and release checks helped bring deployment issues down by roughly 30%.',
            'bullets' => [
                'Develop, test and maintain production business applications in object-oriented PHP, Phalcon, MySQL and JavaScript on Linux.',
                'Turn business and data requirements into back-end features, database logic and advanced MySQL reports.',
                'Work with QA on structured testing, defect reproduction and release validation, contributing to a roughly 30% drop in deployment issues.',
                'Keep source control in sync across Bitbucket and GitLab, document changes, and support releases and post-release fixes.',
            ],
            'stack' => ['PHP', 'Phalcon', 'MySQL', 'SQL', 'JavaScript', 'HTML5', 'CSS3', 'Linux', 'Git'],
        ],
        [
            'id' => 'yello',
            'role' => 'Web Developer',
            'org' => 'Yello Media Group',
            'org_note' => null,
            'period' => 'Jun 2022 – May 2025',
            'type' => null,
            'start' => '2022-06',
            'end' => '2025-05',
            'summary' => 'Built and maintained client websites and web apps in WordPress, PHP, JavaScript and React as part of a remote team spread across the region. Connected WooCommerce stores to Caribbean payment gateways such as WiPay, and handled SEO and analytics updates.',
            'bullets' => [
                'Built and maintained responsive client websites and web apps with WordPress, PHP, JavaScript, React and Elementor, working asynchronously with a distributed regional team.',
                'Integrated WooCommerce with Caribbean payment gateways, including WiPay.',
                'Handled analytics and SEO updates, and fixed production issues across WordPress, JavaScript, CSS, plugins and payments.',
                'Worked directly with clients and internal stakeholders on requirements, CMS administration and post-launch changes.',
            ],
            'stack' => ['WordPress', 'Elementor', 'WooCommerce', 'PHP', 'JavaScript', 'React', 'HTML5', 'CSS3'],
        ],
        [
            'id' => 'devius',
            'role' => 'Web Specialist',
            'org' => 'Devius Ltd.',
            'org_note' => null,
            'period' => '2016 – 2022',
            'type' => null,
            // The source résumé gives years only; months here just place the chart bar.
            'start' => '2016-01',
            'end' => '2022-06',
            'summary' => 'Built and maintained client websites and web content. This is where I learned production web work, client communication and how to hit a deadline.',
            'bullets' => [
                'Developed and maintained client websites and web content.',
            ],
            'stack' => ['HTML5', 'CSS3', 'JavaScript', 'WordPress'],
        ],
    ],

    'highlights' => [
        ['value' => '~35%', 'label' => 'shorter order-to-invoice cycle', 'context' => 'Automation pilot, Label House Group'],
        ['value' => '~30%', 'label' => 'fewer deployment issues', 'context' => 'Working with QA, A.V. Knowles & Co.'],
        ['value' => '2,100+', 'label' => 'knowledge-base articles extracted', 'context' => 'Python + Playwright pipeline, Code Canvas'],
    ],

    'skills' => [
        'Languages' => ['JavaScript', 'PHP', 'Python', 'SQL', 'HTML5', 'CSS3'],
        'Frameworks & platforms' => ['React', 'Phalcon', 'WordPress', 'Elementor', 'WooCommerce', 'Gravity Forms'],
        'Data & APIs' => ['MySQL', 'REST APIs', 'Webhooks'],
        'Automation & tooling' => ['Make', 'Zapier', 'n8n', 'Playwright', 'Git', 'Linux', 'Cloudflare'],
    ],

    'education' => [
        [
            'award' => 'MSc, Data Science',
            'status' => 'In progress',
            'school' => 'University of the West Indies, St. Augustine',
            'period' => '2025 – Present',
        ],
        [
            'award' => 'BSc (Hons), Information Systems Management',
            'status' => 'Upper Second Class',
            'school' => 'UWI / ROYTEC',
            'period' => '2016',
        ],
    ],

    'certifications' => [
        ['name' => 'ITIL® 4 Foundation', 'issuer' => 'PeopleCert', 'year' => '2025'],
        ['name' => 'Foundations of UX Design', 'issuer' => 'Google', 'year' => '2023'],
        ['name' => 'Project Management Qualified (PMQ)', 'issuer' => 'MSI', 'year' => '2021'],
        ['name' => 'AngularJS', 'issuer' => 'Coursera', 'year' => null],
    ],

];
