<?php

/*
 * Placeholder listings while the job API is not connected.
 * Every company and role here is fictional.
 *
 * To go live, pass real jobs to the views (view('Home', ['jobs' => $jobs]), and the same for
 * Jobs and JobDetail) using these keys and this file is no longer read:
 *   id, title, company, location, workplace (Remote|Hybrid|On-site),
 *   type (Full-time|Part-time|Contract), salary (string|null), posted_at (any date string),
 *   field (must match a name in job-categories.php), tags (string[]), summary,
 *   description (string), responsibilities, requirements, benefits (string[], all optional),
 *   company_about (string, optional), apply_url (optional - the employer's own application page, e.g. Workable's
 *   application_url; offered as an alternative on the application form. Applying itself happens on this site:
 *   guests go to the register page first, signed-in people to the application form)
 *
 * Only id and title are required. Every other key can be left out and the pages simply skip it.
 */

$about = [
    'Brightwell Studio' => 'A small design studio that builds calm, useful products for health and education teams.',
    'Northline Labs' => 'A product company making scheduling software for service businesses, with a team spread across three time zones.',
    'Fernbrook Health' => 'A community health provider supporting families across the north of England with care close to home.',
    'Harbor & Pine' => 'A fast-growing home and lifestyle brand with a loyal customer base and a bias for doing things well.',
    'Lumen Finance' => 'A money app that explains finances in plain language and treats every customer question as worth answering.',
    'Cedar Ridge Logistics' => 'A regional logistics company moving goods across Europe with a strong focus on reliability.',
    'Orchard Learning' => 'An education company creating warm, practical learning for schools and independent learners.',
    'Tidewater Bank' => 'A regional bank that hires early-career talent and promotes from within.',
];

$jobs = [
    [
        'id' => 'sample-1', 'title' => 'Senior Product Designer', 'company' => 'Brightwell Studio',
        'location' => 'Remote, worldwide', 'workplace' => 'Remote', 'type' => 'Full-time', 'salary' => '$95k – $125k',
        'posted_at' => now()->subHours(3), 'field' => 'Design', 'tags' => ['Figma', 'Design systems', 'Research'],
        'summary' => 'Shape the product experience for a growing team that ships weekly.',
        'description' => 'You will lead design for our core product, working side by side with engineers and researchers from the first sketch to the shipped feature. We value clear thinking, careful craft and people who like explaining their decisions.',
        'responsibilities' => ['Own the end-to-end design of new product features', 'Grow and maintain our design system', 'Run research sessions and turn what you learn into decisions'],
        'requirements' => ['5+ years designing digital products', 'A portfolio that shows your process, not just the final screens', 'Comfortable presenting and receiving feedback'],
        'benefits' => ['Work from anywhere', 'Yearly learning budget', '28 days of paid leave'],
    ],
    [
        'id' => 'sample-2', 'title' => 'Full-stack Engineer', 'company' => 'Northline Labs',
        'location' => 'Toronto, Canada', 'workplace' => 'Hybrid', 'type' => 'Full-time', 'salary' => '$110k – $145k',
        'posted_at' => now()->subHours(7), 'field' => 'Engineering', 'tags' => ['Laravel', 'Vue', 'PostgreSQL'],
        'summary' => 'Build and own customer-facing features from first idea to launch.',
        'description' => 'Join a small product team where engineers talk to customers and own what they ship. You will work across a Laravel and Vue codebase and help keep our software fast, tested and a pleasure to change.',
        'responsibilities' => ['Design, build and ship features across the stack', 'Review code and help teammates grow', 'Improve performance and reliability of our core services'],
        'requirements' => ['3+ years building web applications', 'Strong grasp of relational databases', 'A habit of writing tests and clear commit messages'],
        'benefits' => ['Three office days a week, the rest remote', 'Health and dental cover', 'Equity in the company'],
    ],
    [
        'id' => 'sample-3', 'title' => 'Community Nurse Coordinator', 'company' => 'Fernbrook Health',
        'location' => 'Manchester, UK', 'workplace' => 'On-site', 'type' => 'Full-time', 'salary' => '£38k – £46k',
        'posted_at' => now()->subHours(20), 'field' => 'Healthcare', 'tags' => ['Patient care', 'Scheduling', 'Teamwork'],
        'summary' => 'Coordinate care for local families and lead a small, close-knit team.',
        'description' => 'You will coordinate home visits and clinic appointments for families in your area, support a team of community nurses and be a steady, friendly point of contact for patients and carers.',
        'responsibilities' => ['Plan and coordinate weekly visit schedules', 'Support nurses with complex cases', 'Liaise with GPs, social care and families'],
        'requirements' => ['Registered nurse with current NMC registration', '2+ years of community or primary care experience', 'Calm under pressure and a natural communicator'],
        'benefits' => ['NHS-style pension', 'Paid training and development', 'Supportive, well-staffed team'],
    ],
    [
        'id' => 'sample-4', 'title' => 'Growth Marketing Manager', 'company' => 'Harbor & Pine',
        'location' => 'Austin, USA', 'workplace' => 'Hybrid', 'type' => 'Full-time', 'salary' => '$85k – $105k',
        'posted_at' => now()->subDay(), 'field' => 'Marketing', 'tags' => ['SEO', 'Lifecycle email', 'Analytics'],
        'summary' => 'Own acquisition and retention for a fast-growing consumer brand.',
        'description' => 'You will own how new customers find us and why they stay. Expect a mix of experiments, reporting and close work with design and product to make every touchpoint feel like us.',
        'responsibilities' => ['Plan and run acquisition and retention campaigns', 'Own our email and SEO programmes', 'Report on what works and what to stop'],
        'requirements' => ['4+ years in growth or performance marketing', 'Confident with analytics tools and A/B testing', 'Clear writer with good taste'],
        'benefits' => ['Flexible hybrid schedule', 'Product discount and allowance', 'Quarterly team days out'],
    ],
    [
        'id' => 'sample-5', 'title' => 'Customer Support Specialist', 'company' => 'Lumen Finance',
        'location' => 'Remote, Europe', 'workplace' => 'Remote', 'type' => 'Part-time', 'salary' => '€22k – €28k',
        'posted_at' => now()->subDays(2), 'field' => 'Customer support', 'tags' => ['Empathy', 'Live chat', 'Problem solving'],
        'summary' => 'Help customers feel confident about their money, one conversation at a time.',
        'description' => 'You will answer customer questions by chat and email, explain things clearly and kindly, and pass on what you learn so we can improve the product. Twenty hours a week, with some flexibility around shifts.',
        'responsibilities' => ['Respond to customers by chat and email', 'Document answers for the help centre', 'Flag recurring issues to the product team'],
        'requirements' => ['Fluent written English', 'Patience and a friendly tone', 'Comfortable learning financial basics'],
        'benefits' => ['Fully remote within Europe', 'Paid training', 'Shifts agreed a month ahead'],
    ],
    [
        'id' => 'sample-6', 'title' => 'Operations Analyst', 'company' => 'Cedar Ridge Logistics',
        'location' => 'Berlin, Germany', 'workplace' => 'On-site', 'type' => 'Contract', 'salary' => '€55k – €68k',
        'posted_at' => now()->subDays(2)->subHours(5), 'field' => 'Operations', 'tags' => ['SQL', 'Forecasting', 'Excel'],
        'summary' => 'Turn delivery data into decisions that keep every route on time.',
        'description' => 'A twelve-month contract to analyse delivery performance and help the operations team plan better. You will work with large datasets and present findings to people who run the routes every day.',
        'responsibilities' => ['Build reports on delivery performance', 'Forecast demand by region and season', 'Present findings to operations managers'],
        'requirements' => ['Strong SQL and spreadsheet skills', 'Experience in logistics or supply chain is a plus', 'Clear, practical communication'],
        'benefits' => ['Twelve-month contract with extension likely', 'Central Berlin office', 'Relocation support'],
    ],
    [
        'id' => 'sample-7', 'title' => 'Content Writer', 'company' => 'Orchard Learning',
        'location' => 'Remote, worldwide', 'workplace' => 'Remote', 'type' => 'Contract', 'salary' => '$40 – $55 / hr',
        'posted_at' => now()->subDays(3), 'field' => 'Marketing', 'tags' => ['Editing', 'Education', 'Research'],
        'summary' => 'Write clear, warm lessons that make learning feel within reach.',
        'description' => 'We are looking for a writer who can turn complex ideas into lessons that feel friendly and clear. You will work with our curriculum team on short courses and study guides.',
        'responsibilities' => ['Write and edit lesson content', 'Research topics with subject experts', 'Keep our tone and style guide consistent'],
        'requirements' => ['Published writing or editing experience', 'Ability to explain difficult ideas simply', 'Reliable with deadlines'],
        'benefits' => ['Flexible hours', 'Long-term work for the right person', 'Remote from anywhere'],
    ],
    [
        'id' => 'sample-8', 'title' => 'Junior Data Analyst', 'company' => 'Tidewater Bank',
        'location' => 'London, UK', 'workplace' => 'Hybrid', 'type' => 'Full-time', 'salary' => '£34k – £40k',
        'posted_at' => now()->subDays(4), 'field' => 'Finance', 'tags' => ['Python', 'Dashboards', 'Reporting'],
        'summary' => 'Start your analytics career with a team that mentors and promotes from within.',
        'description' => 'This is an early-career role in our analytics team. You will build dashboards and reports for teams across the bank and learn from experienced analysts who enjoy teaching.',
        'responsibilities' => ['Build and maintain weekly dashboards', 'Clean and prepare data for analysis', 'Support senior analysts on ad hoc requests'],
        'requirements' => ['Degree or equivalent experience in a numerate subject', 'Some Python or SQL', 'Curious and careful with detail'],
        'benefits' => ['Structured mentoring programme', 'Hybrid working', 'Study support towards professional exams'],
    ],
    [
        'id' => 'sample-9', 'title' => 'UX Researcher', 'company' => 'Brightwell Studio',
        'location' => 'Remote, Europe', 'workplace' => 'Remote', 'type' => 'Contract', 'salary' => '€450 – €550 / day',
        'posted_at' => now()->subDays(4)->subHours(6), 'field' => 'Design', 'tags' => ['Interviews', 'Usability testing', 'Synthesis'],
        'summary' => 'Learn what people really need and help the team build around it.',
        'description' => 'A six-month contract to run research for two health products. You will plan studies, talk to users and turn what you hear into clear recommendations the whole team can act on.',
        'responsibilities' => ['Plan and run interviews and usability tests', 'Synthesise findings into clear recommendations', 'Share insights with design and engineering'],
        'requirements' => ['3+ years in UX research', 'Experience with qualitative methods', 'Comfortable working with sensitive topics'],
        'benefits' => ['Six-month contract with extension option', 'Remote within Europe', 'Collaborative, low-ego team'],
    ],
    [
        'id' => 'sample-10', 'title' => 'Mobile Engineer, React Native', 'company' => 'Northline Labs',
        'location' => 'Remote, worldwide', 'workplace' => 'Remote', 'type' => 'Full-time', 'salary' => '$100k – $135k',
        'posted_at' => now()->subDays(5), 'field' => 'Engineering', 'tags' => ['React Native', 'TypeScript', 'Testing'],
        'summary' => 'Make our mobile app feel fast, polished and dependable.',
        'description' => 'You will take ownership of our React Native app used by thousands of service teams every day. The focus is quality: smooth interactions, solid tests and a calm release process.',
        'responsibilities' => ['Build new features in React Native', 'Improve app performance and stability', 'Own the release process with the team'],
        'requirements' => ['3+ years of mobile development', 'Strong TypeScript skills', 'Experience publishing to both app stores'],
        'benefits' => ['Work from anywhere', 'Equipment budget', 'Equity in the company'],
    ],
    [
        'id' => 'sample-11', 'title' => 'Registered Nurse', 'company' => 'Fernbrook Health',
        'location' => 'Leeds, UK', 'workplace' => 'On-site', 'type' => 'Part-time', 'salary' => '£17 – £21 / hr',
        'posted_at' => now()->subDays(5)->subHours(8), 'field' => 'Healthcare', 'tags' => ['Clinical care', 'Community', 'Weekends'],
        'summary' => 'Deliver patient-centred care in a friendly neighbourhood clinic.',
        'description' => 'Join our Leeds clinic on a part-time basis, with three shifts a week including some weekends. You will care for a regular group of patients and work closely with a supportive team.',
        'responsibilities' => ['Provide nursing care in clinic and at home', 'Keep accurate patient records', 'Support colleagues across shifts'],
        'requirements' => ['Current NMC registration', 'Experience in a community or clinic setting', 'Warm, patient manner'],
        'benefits' => ['Predictable monthly rota', 'Paid training', 'Pension scheme'],
    ],
    [
        'id' => 'sample-12', 'title' => 'Account Executive', 'company' => 'Harbor & Pine',
        'location' => 'Austin, USA', 'workplace' => 'On-site', 'type' => 'Full-time', 'salary' => '$70k – $90k + commission',
        'posted_at' => now()->subDays(6), 'field' => 'Sales', 'tags' => ['B2B', 'Negotiation', 'CRM'],
        'summary' => 'Build lasting relationships with retail partners across the region.',
        'description' => 'You will manage and grow a portfolio of retail partners, from first conversation to long-term relationship. This role suits someone who enjoys being out in stores as much as on calls.',
        'responsibilities' => ['Manage a portfolio of retail accounts', 'Negotiate terms and seasonal plans', 'Report on pipeline and forecasts'],
        'requirements' => ['2+ years in B2B sales or account management', 'Confident negotiator', 'Willing to travel locally'],
        'benefits' => ['Uncapped commission', 'Company car allowance', 'Product allowance'],
    ],
    [
        'id' => 'sample-13', 'title' => 'Primary School Teacher', 'company' => 'Orchard Learning',
        'location' => 'Bristol, UK', 'workplace' => 'On-site', 'type' => 'Full-time', 'salary' => '£32k – £44k',
        'posted_at' => now()->subDays(7), 'field' => 'Education', 'tags' => ['Key Stage 2', 'Curriculum', 'Pastoral care'],
        'summary' => 'Teach, inspire and support a class of curious Year 4 pupils.',
        'description' => 'A full-time class teacher role for September in one of our partner primary schools. You will plan engaging lessons, support every child to progress and work closely with families.',
        'responsibilities' => ['Plan and teach the Year 4 curriculum', 'Track and support pupil progress', 'Communicate regularly with parents and carers'],
        'requirements' => ['Qualified teacher status', 'Experience at Key Stage 2', 'Enthusiasm for helping children learn'],
        'benefits' => ['Teachers’ pension scheme', 'Protected planning time', 'Strong induction and mentoring'],
    ],
    [
        'id' => 'sample-14', 'title' => 'Financial Controller', 'company' => 'Tidewater Bank',
        'location' => 'London, UK', 'workplace' => 'Hybrid', 'type' => 'Full-time', 'salary' => '£85k – £105k',
        'posted_at' => now()->subDays(8), 'field' => 'Finance', 'tags' => ['IFRS', 'Month-end close', 'Leadership'],
        'summary' => 'Lead financial reporting and controls for a growing regional bank.',
        'description' => 'You will lead a team of eight accountants and own the quality of our financial reporting. This is a senior role with real influence over how the finance function grows.',
        'responsibilities' => ['Lead month-end and year-end close', 'Own statutory and management reporting', 'Strengthen financial controls and processes'],
        'requirements' => ['Qualified accountant (ACA, ACCA or CIMA)', 'Experience leading a finance team', 'Strong knowledge of IFRS'],
        'benefits' => ['Hybrid working', 'Annual bonus', 'Private health cover'],
    ],
    [
        'id' => 'sample-15', 'title' => 'Customer Success Manager', 'company' => 'Lumen Finance',
        'location' => 'Remote, Europe', 'workplace' => 'Remote', 'type' => 'Full-time', 'salary' => '€48k – €60k',
        'posted_at' => now()->subDays(9), 'field' => 'Customer support', 'tags' => ['Onboarding', 'Retention', 'Partnerships'],
        'summary' => 'Help our business customers get real value from Lumen from day one.',
        'description' => 'You will look after a portfolio of business customers, guide them through onboarding and make sure they keep getting value. You will also bring their feedback straight to the product team.',
        'responsibilities' => ['Onboard and support business customers', 'Run regular check-ins and reviews', 'Bring customer feedback to the product team'],
        'requirements' => ['2+ years in customer success or account management', 'Excellent communication skills', 'Interest in fintech'],
        'benefits' => ['Fully remote within Europe', 'Home office budget', 'Annual team retreat'],
    ],
    [
        'id' => 'sample-16', 'title' => 'Warehouse Operations Lead', 'company' => 'Cedar Ridge Logistics',
        'location' => 'Hamburg, Germany', 'workplace' => 'On-site', 'type' => 'Full-time', 'salary' => '€46k – €58k',
        'posted_at' => now()->subDays(10), 'field' => 'Operations', 'tags' => ['Team lead', 'Safety', 'Inventory'],
        'summary' => 'Lead a day shift team and keep one of our busiest warehouses running smoothly.',
        'description' => 'You will lead a day shift team of around twenty, keep safety and quality high and work with planners to hit daily targets. A hands-on role for someone who likes a busy floor.',
        'responsibilities' => ['Lead and coach a day shift team', 'Maintain safety and quality standards', 'Work with planning to meet daily targets'],
        'requirements' => ['2+ years leading a warehouse team', 'Knowledge of warehouse systems', 'German and English'],
        'benefits' => ['Stable day shifts', 'Shift allowance', 'Clear route to site manager'],
    ],
    [
        'id' => 'sample-17', 'title' => 'Brand Designer', 'company' => 'Harbor & Pine',
        'location' => 'Austin, USA', 'workplace' => 'Hybrid', 'type' => 'Contract', 'salary' => '$55 – $70 / hr',
        'posted_at' => now()->subDays(11), 'field' => 'Design', 'tags' => ['Branding', 'Packaging', 'Illustration'],
        'summary' => 'Give our new seasonal range a distinctive look and feel.',
        'description' => 'A four-month contract to design packaging, campaign visuals and in-store materials for our next product range. You will work closely with our creative director and marketing team.',
        'responsibilities' => ['Design packaging and campaign visuals', 'Extend our brand guidelines', 'Prepare files for print and digital'],
        'requirements' => ['Strong portfolio in brand or packaging design', 'Skilled in Illustrator and Photoshop', 'Meets deadlines with calm'],
        'benefits' => ['Four-month contract', 'Hybrid working', 'Creative, supportive team'],
    ],
    [
        'id' => 'sample-18', 'title' => 'DevOps Engineer', 'company' => 'Cedar Ridge Logistics',
        'location' => 'Remote, Europe', 'workplace' => 'Remote', 'type' => 'Full-time', 'salary' => '€70k – €90k',
        'posted_at' => now()->subDays(12), 'field' => 'Engineering', 'tags' => ['AWS', 'Terraform', 'Kubernetes'],
        'summary' => 'Make our infrastructure reliable, observable and easy to deploy to.',
        'description' => 'You will improve how we build, deploy and monitor the systems that track thousands of deliveries a day. You will work with small product teams and treat reliability as a feature.',
        'responsibilities' => ['Maintain and improve our cloud infrastructure', 'Build CI/CD pipelines for product teams', 'Set up monitoring and on-call practices'],
        'requirements' => ['3+ years in DevOps or SRE', 'Strong AWS and Terraform experience', 'Experience running Kubernetes in production'],
        'benefits' => ['Fully remote within Europe', 'Learning and conference budget', 'Fair on-call compensation'],
    ],
    [
        'id' => 'sample-19', 'title' => 'Software Developer', 'company' => 'Savannah Labs',
        'location' => 'Lagos, Nigeria', 'workplace' => 'Hybrid', 'type' => 'Full-time', 'salary' => '$30k – $48k',
        'posted_at' => now()->subHours(11), 'field' => 'Engineering', 'tags' => ['Node.js', 'React', 'APIs'],
        'summary' => 'Build payment tools used by small businesses across West Africa.',
        'description' => 'You will join a product team building simple payment and invoicing tools for small businesses. Expect to ship often, talk to customers and own features from idea to launch.',
        'responsibilities' => ['Build and maintain features across web and API', 'Work with designers and support to fix real customer problems', 'Write tests and review teammates’ code'],
        'requirements' => ['2+ years of professional development', 'Comfortable with JavaScript on both front and back end', 'Clear communicator'],
        'benefits' => ['Hybrid working from our Lagos office', 'Health insurance', 'Learning budget'],
    ],
    [
        'id' => 'sample-20', 'title' => 'Field Operations Coordinator', 'company' => 'Riftline Logistics',
        'location' => 'Nairobi, Kenya', 'workplace' => 'On-site', 'type' => 'Full-time', 'salary' => null,
        'posted_at' => now()->subDays(1)->subHours(6), 'field' => 'Operations',
        'summary' => 'Keep deliveries moving across East Africa with a busy, friendly team.',
    ],
    [
        'id' => 'sample-21', 'title' => 'Product Marketing Lead', 'company' => 'Tablemount Studio',
        'location' => 'Cape Town, South Africa', 'workplace' => 'Hybrid', 'type' => 'Full-time', 'salary' => 'R70k – R90k / month',
        'posted_at' => now()->subDays(3)->subHours(2), 'field' => 'Marketing', 'tags' => ['Positioning', 'Launches'],
        'summary' => 'Shape how our design tools are explained and launched.',
    ],
    [
        'id' => 'sample-22', 'title' => 'Financial Analyst', 'company' => 'Palmcrest Capital',
        'location' => 'Dubai, UAE', 'workplace' => 'On-site', 'type' => 'Full-time', 'salary' => 'AED 18k – 24k / month',
        'posted_at' => now()->subDays(6)->subHours(4), 'field' => 'Finance', 'tags' => ['Modelling', 'Reporting'],
        'summary' => 'Support investment decisions with clear, careful analysis.',
    ],
    [
        'id' => 'sample-23', 'title' => 'Customer Care Agent', 'company' => 'Brightline Care',
        'location' => 'Manila, Philippines', 'workplace' => 'On-site', 'type' => 'Full-time', 'salary' => null,
        'posted_at' => now()->subDays(8)->subHours(3), 'field' => 'Customer support',
        'summary' => 'Be the friendly voice for customers in a growing care team.',
    ],
    [
        'id' => 'sample-24', 'title' => 'Data Engineer', 'company' => 'Bluegum Analytics',
        'location' => 'Sydney, Australia', 'workplace' => 'Hybrid', 'type' => 'Contract', 'salary' => 'A$700 – A$850 / day',
        'posted_at' => now()->subDays(9)->subHours(7), 'field' => 'Engineering', 'tags' => ['dbt', 'Snowflake', 'Python'],
        'summary' => 'Build reliable data pipelines for a team of analysts and researchers.',
    ],
];

return array_map(function (array $job) use ($about) {
    $job['posted_at'] = $job['posted_at']->toIso8601String();
    $job['company_about'] = $about[$job['company']] ?? null;

    return $job;
}, $jobs);
