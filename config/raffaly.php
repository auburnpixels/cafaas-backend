<?php

return [

    'internal' => [
        'email' => env('INTERNAL_NOTIFICATION_EMAIL', 'liam@raffaly.com'),
    ],

    'trustpilot' => [
        'email' => 'raffaly.com+165aaf4240@invite.trustpilot.com',
    ],

    'whatsapp' => [
        'number' => '+447457406371',
    ],

    'checkout' => [
        'expiry_minutes' => 10,
    ],

    'excluded_tenant_hosts' => env('EXCLUDED_TENANT_HOSTS', '0.0.0.0,raffaly.test,raffaly.co.uk,raffaly.com'),

    'admin' => [
        'name' => env('APP_NAME'),
        'email' => 'operations@raffaly.co.uk',
        'password' => env('PAKAPOU_APP_PASSWORD'),
    ],

    'commission' => 10,
    'access_link_commission' => 5,

    'host_guarantee' => 30,
    'winner_guarantee' => 70,

    'days_to_accept' => 30,

    'trust_payments' => [
        'email' => env('PAKAPOU_TRUST_EMAIL', 'liamwalder@hotmail.co.uk'),
        'site_reference' => env('PAKAPOU_SITE_REFERENCE', 'test_mcdonaldlawrenceltd102366'),
    ],

    'youtube_videos' => [
        'creating_a_competition' => 'https://www.youtube.com/watch?v=UeRk0Sd8FUY',
        'drawing_raffle_results' => 'https://www.youtube.com/watch?v=Oi08hT21BPw',
        'free_tickets_raffles' => 'https://www.youtube.com/watch?v=BpPlWiGp24g',
        'offering_discount_raffles' => 'https://www.youtube.com/watch?v=dtFfHRgZ3tQ',
        'unlimited_tickets_raffles' => 'https://youtu.be/o_TP486fMxg',
    ],

    'faqs' => [

        'temp' => [
            [
                'question' => 'How do I play?',
                'answer' => 'You select the competition you wish to enter, indicate how many tickets you need, and give an answer to the required question. We will automatically draw the raffles\'s winner when it has concluded. We hope to deliver the reward to you in 7 days if you are the raffle winner.',
            ],
            [
                'question' => 'How do I get my prize?',
                'answer' => 'If you are a winner, further instructions on how to claim your prize can be found on your "Winning tickets" in your account. If you checked out as a guest, you will instead receive a further email with these same instructions.',
            ],
            [
                'question' => 'Can I view the live draw?',
                'answer' => 'No, all our raffle draws are done automatically. However, we may also do live draws via stream, or in person, for particular competitions that you are welcome to view or attend.',
            ],
            [
                'question' => 'Can I exchange my prize?',
                'answer' => 'No, the prizes are set and final.',
            ],
            [
                'question' => 'How do you select a winner?',
                'answer' => 'Each ticket bought will receive a random number. This can also see on a users account. When the raffle has ended, we automatically choose a random winner. If we are live streaming, viewers can watch the screen live via social media.',
            ],
            [
                'question' => 'When does a competition close?',
                'answer' => 'A competition will close either: when all tickets have been bought or the competition end time has been reached. Our countdown timer indicates how much time is remaining in each competition. If the competition ends due to all tickets being sold, the automatic draw will be brought closer.',
            ],
            [
                'question' => 'How do I pay?',
                'answer' => 'As normal, we accept all major credit and debit cards. These transactions are safe. We also provide a free admission option, which is detailed in our Terms.',
            ],
            [
                'question' => 'I want to close my account?',
                'answer' => 'To close your account, send us an email at contact@raffaly.com.',
            ],
            [
                'question' => 'How do I enter via post?',
                'answer' => 'You can enter for free by post. It outlines how to enter via a postal route in our terms and conditions.',
            ],
            [
                'question' => 'Do I need to be 18+ to enter competitions?',
                'answer' => 'Yes, in order to participate in any of our competitions, players must be at least 18 years old. By entering, you are confirming you are over 18 years old. A competition will be redrawn if it turns out that any of the winners are not at least eighteen years old.',
            ],
        ],
    ],

    //    'faqs' => [
    //        [
    //            'title' => 'General',
    //            'faqs' => [
    //                [
    //                    'question' => 'Why should I use ' . config('app.name') . '?',
    //                    'answer' =>
    //                        config('app.name') . ' is the only service like it on the market. It has been built bespoke,
    //                        with the only focus to start and manage a skills-based competition website. Going to a software
    //                        company to build a website like what we can give you, they would charge thousands. We offer
    //                        this at a low monthly price, which you could just tie into your ticket sales anyway!
    //                    '
    //                ], [
    //                    'question' => 'How quick can I get up and running?',
    //                    'answer' => '
    //                        Instantly! With ' . config('app.name') . ', your website becomes instantly available on a
    //                        subdomain, which you get to choose during sign up. So for example, if your website is called "Top Giveaway",
    //                        you may choose your URL to be topgiveaway.raffaly.co.uk.
    //                        <br /><br />
    //                        With the above in the mind, the only thing you can\'t start instantly is taking payments. The
    //                        reason for this is you need to be approved by our payment provider. This only take a couple
    //                        of days and we will go into more detail below.
    //                        <br /><br />
    //                        However, while waiting for approval, you can still use your website, adding products, competitions,
    //                        changing to your brand colours and more.
    //                    '
    //                ], [
    //                    'question' => 'What is the process or getting up and running?',
    //                    'answer' => '
    //                        We have tried to make the process as simple as possible, splitting it into three steps.
    //                        <a href="/#process" target="_blank"><u>Click here</u></a> to check our these steps.
    //                    '
    //                ], [
    //                    'question' => 'Can only Limited Companies or sole trader\'s start a skills-based competition website?',
    //                    'answer' => '
    //                        Skills-based competition websites are considered "high-risk", and due to this, only registered
    //                        Limited Companies and sole traders are allowed to start one. Without this, you will not
    //                        be accepted by our payment provider.
    //                    '
    //                ],[
    //                    'question' => 'Is my website legal by default?',
    //                    'answer' => '
    //                        If you are registered as a Limited Company or sole trader, then 99% yes! The only thing
    //                        you need to do once signed up is add terms and conditions and privacy policy.
    //                    '
    //                ],
    //            ]
    //        ],
    //        [
    //            'title' => 'Payments',
    //            'faqs' => [
    //                [
    //                    'question' => 'Which payment provider do you use?',
    //                    'answer' => '
    //                        The payment provider we have partnered with is
    //                        <a href="https://www.trustpayments.com/" target="_blank"><u>Target Payments</u></a>.
    //                    '
    //                ], [
    //                    'question' => 'Why do you use Trust Payments?',
    //                    'answer' => '
    //                        <a href="https://www.trustpayments.com/" target="_blank"><u>Target Payments</u></a> are a
    //                        leading company in taking payments for big companies. They also specialise in taking payments
    //                        for "high-risk businesses", exactly what a skills-based competition website is considered.
    //                        Aside from this, the founders and creators of ' . config('app.name') . ' have used
    //                        <a href="https://www.trustpayments.com/" target="_blank"><u>Target Payments</u></a> in multiple other
    //                        projects over the years.
    //                    '
    //                ], [
    //                    'question' => 'Why can’t I take payment using Paypal or other popular payments gateways?',
    //                    'answer' => '
    //                        Running a skills-based competition website is considered "high-risk" and, due to this, Paypal and Stripe
    //                        will not take payments for "high-risk" websites.
    //                    '
    //                ], [
    //                    'question' => 'When can I start taking payments?',
    //                    'answer' => '
    //                        You can start taking payments as soon you have been approved by our payment provider. We will
    //                        also email you when you have been approved and your website can start taking payments.
    //                    '
    //                ], [
    //                    'question' => 'How often will I get payouts?',
    //                    'answer' => '
    //                        This is something the payment provider will discuss with you when they contact you.
    //                    '
    //                ], [
    //                    'question' => 'How do I get approved by your payment provider?',
    //                    'answer' => '
    //                        We have tried to make this as simple as possible.
    //                        <br /><br />
    //                        When you log in to your admin panel,
    //                        there are three tasks to undertake. You need to add content to your terms and conditions
    //                        and privacy page. Add your first competition. And then submit a simple form. We walk
    //                        you through all of this to make it easy for you.
    //                        <br /><br />
    //                        After this, within 1-2 days, a dedicated account manager will be in touch with you. This is
    //                        mostly just an introduction call, they will discuss the role they play and discuss how
    //                        you can pay the money to them, the one-off fee and the monthly.
    //                        <br/><br />
    //                        Once all this has been completed, they will get in touch with ' . config('app.name') . '
    //                        ,and then we turn on payments for your website.
    //                    '
    //                ], [
    //                    'question' => 'What charges will I pay per transaction?',
    //                    'answer' => '
    //                        For each transaction through your website, you will be charged 1.95% + 13p.
    //                    '
    //                ]
    //            ]
    //        ],
    //        [
    //            'title' => 'Subscription',
    //            'faqs' => [
    //                [
    //                    'question' => 'How much will I be paying monthly?',
    //                    'answer' => '
    //                        You will be paying £71.99 monthly. £22.00 of this will be paid to our payment provider
    //                        and £49.99 will be paid to ' . config('app.name') . '. There is also a £495
    //                        one-off fee to be paid to our payment provider.
    //                    '
    //                ], [
    //                    'question' => 'How do I pay my monthly free and one-off fee?',
    //                    'answer' => '
    //                        For the money to our payment provider, they will contact you and tell you best way on how you
    //                        can provide this and set up the monthly payments. For the money to ' . config('app.name') . ',
    //                        we will ask you to provide payment details when you log in to the admin panel of your website.
    //                    '
    //                ], [
    //                    'question' => 'When will my subscription start?',
    //                    'answer' => '
    //                        We will only start your subscription when you have been approved by our payment provider.
    //                        An email will also be sent to you when your subscription has started.
    //                    '
    //                ]
    //            ]
    //        ],
    //        [
    //            'title' => 'Your website',
    //            'faqs' => [
    //                [
    //                    'question' => 'Can I brand my website?',
    //                    'answer' => '
    //                        Absolutely, that\'s the whole point! You can upload your own logo, add you own favicon
    //                        and change all colours related to your website.
    //                    '
    //                ], [
    //                    'question' => 'Will my website rank on search engines and be found by people?',
    //                    'answer' => '
    //                        Yes, but it requires a bit of effort from yourself.
    //                        We offer you SEO settings on each page, competition and winners, so you can fill these
    //                        to make sure your website is found. We also generate a sitemap every day, which you can
    //                        submit to search engines so they know about your new pages and competitions.
    //                        You can find this at yourdomain/sitemap.xml.
    //                    '
    //                ], [
    //                    'question' => 'What should I price my tickets at?',
    //                    'answer' => '
    //                        This is up to you. Each competition has a setting in which you can set the price off a single
    //                        ticket. You can find this setting when creating or editing a competition.
    //                    '
    //                ], [
    //                    'question' => 'How many tickets should I offer for each competition?',
    //                    'answer' => '
    //                        The maximum available tickets can be set per competition. You can also set how many tickets
    //                        one user is allowed. You can find these settings when creating or editing a competition.
    //                    '
    //                ], [
    //                    'question' => 'Can I offer discounts on competitions?',
    //                    'answer' => '
    //                        Yes. You can offer percentage based or monetary discounts on single tickets or groups of tickets.
    //                        You can find these settings when creating or editing a competition.
    //                    '
    //                ], [
    //                    'question' => 'I have a newsletter, can I subscribe my users?',
    //                    'answer' => '
    //                        We have built in newsletter integration with Mailchimp. You can add your MailChimp credentials
    //                        on the settings page. The option to subscribe to your newsletter will be offered on sign up
    //                        and in their profile.
    //                    '
    //                ]
    //            ]
    //        ]
    //    ]

    'draw_events' => [
        'enabled' => env('DRAW_EVENTS_ENABLED', true),
        'chain_hashing' => env('DRAW_EVENTS_CHAIN_HASHING', true),
        'store_ip_address' => env('DRAW_EVENTS_STORE_IP', true),
        'store_user_agent' => env('DRAW_EVENTS_STORE_UA', true),
        'daily_digest_enabled' => env('DRAW_EVENTS_DAILY_DIGEST', true),
        'digest_storage_path' => 'event-digests',
        'digest_s3_bucket' => env('DRAW_EVENTS_DIGEST_S3_BUCKET', null),
    ],

    'rng' => [
        'algorithm' => env('RNG_ALGORITHM', 'random_bytes'),
        'version' => env('RNG_VERSION', '1.0'),
    ],

    'postal_entry' => [
        'address' => [
            'line1' => '12 Alberta Close',
            'city' => 'Blackburn',
            'postcode' => 'BB2 7DU',
            'country' => 'United Kingdom',
        ],
        'full_address' => '12 Alberta Close, Blackburn, BB2 7DU, United Kingdom',
    ],

    'testimonials' => [
        [
            'content' => "We're excited to be using Raffaly for the second time after the success of our first raffle for our school's summer fair. The platform is incredibly user-friendly, making navigation easy for both our team and participants. Many users appreciated the option to buy tickets without needing to create an account, and we loved being able to track entries in real time.<br /><br />Liam and his customer support team were outstanding; they addressed our questions and any issues we had promptly and thoroughly. Liam consistently responded to my emails quickly and personally, which I really appreciated. Our raffle exceeded our fundraising expectations, and we received fantastic feedback from everyone involved. I highly recommend Raffaly for anyone looking to host a successful online raffle!",
            'author' => 'St John Fisher PTFA',
            'image' => 'profile-images/1717506875_SUMMER FAIR 2024 FULL PACK.webp',
            'username' => 'sjfptfa',
        ],
        [
            'content' => "Raffaly is easy to use and Liam was brilliant at answering questions quickly and helping us to set up our raffle. People really appreciated being able to participate as a guest without having to create a profile too. The commission is competitively priced, which matters as we're raising funds for charity. We hope to continue using Raffaly for our 2 yearly raffles going forward.",
            'author' => 'Minchinhampton School PTA',
            'image' => 'profile-images/2020102009043816380478145f8ea8268b8e53.webp',
            'username' => 'minchinhampton_pta',
        ],
        [
            'content' => "We are a PTA and have used Raffaly this year for the first time after having used a competitor twice previously. What sold it for me, apart from the lower commission, was the ability to checkout as a guest, as many people mentioned they hadn't bought tickets because they didn't want to create an account.<br /><br />Liam was always kind, approachable, helpful and very responsive! Always willing to make changes to accommodate our needs. It has been a real pleasure to be able to work with a real person and not a customer service agency which is what we find in most places these days! The whole process was easy and straightforward and the little hurdles we found were mended straight away! I couldn't rate them highly enough! Definitely recommend.",
            'author' => 'Beaudesert Lower School PTA',
            'image' => 'profile-images/avatar_646e759eb0639.webp',
            'username' => 'ptabeaudesert',
        ],
        [
            'content' => 'The Raffaly platform was easy to use and the team were very quick to respond to any questions we had. The platform enabled us to share our raffle widely, and also track ticket sales. Once the raffle was drawn winners were notified quickly. Thanks to the Raffaly platform our raffle was a huge success!',
            'author' => 'Riversdale Primary School PTC',
            'image' => 'profile-images/20230510205744607079284645c0548c8eba3.webp',
            'username' => 'riversdale_school_ptc',
        ],
        [
            'content' => 'Easy setup, multiple raffles at once for free, fast and friendly customer service. We recommend and will use the site again!',
            'author' => 'Swansea LGBT Society',
            'image' => 'profile-images/1732226431_Progress_Pride.webp',
            'username' => 'swanseaunilgbtq',
        ],
        [
            'content' => 'Having used similar platforms to promote Pure Roasters coffee, I found Raffaly much easier to use. The site is secure and the raffle was well promoted on socials with no hidden charges. Communication with Liam in support was clear and attentive. We will use this raffle site again.',
            'author' => 'Pure Roasters Coffee',
            'image' => 'profile-images/1732610444_Logo Black.webp',
            'username' => 'pureroasters',
            'id' => 'pure-coffee-roasters',
        ],
        [
            'content' => 'Using Raffaly for our game-worn player edition Grinch Christmas jerseys really streamlined our process, providing some great features making it easy for our fans to enter the raffle. We had a slight technical glitch which Liam was on hand over the weekend to sort immediately for us which was greatly appreciated and fantastic customer service. When we do another raffle, we will be sure to be using Raffaly and recommend to others too!',
            'author' => 'Hull Seahawks',
            'image' => 'profile-images/1734182901_Badge Zoomed In.webp',
            'username' => 'hullseahawks',
            'id' => 'hullseahawks',
        ],
        [
            'content' => "We are a school PTA who need really easy-to-use tools that will help us raise the most funds for our school. We have used other platforms in the past and I am so pleased we have now found Raffaly! Liam was so helpful and available to support us from the start, including helping us out when we made an error with our raffle items - it felt like we had another team member working with us and it took away a lot of stress. The platform itself was very easy to use for both us backstage and our raffle ticket buyers, and the fees charged were less than we've found anywhere else which meant the vast majority of what we raised actually went to benefit our school and children. We are really happy with the experience and will definitely use Raffaly again!",
            'author' => 'The Friends of St Lawrence',
            'image' => null,
            'username' => 'thefriendsofstlawrence',
        ],
    ],

];
