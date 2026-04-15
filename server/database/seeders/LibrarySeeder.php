<?php

namespace Database\Seeders;

use App\Models\ScopeLibrary;
use App\Models\ServiceLibrary;
use Illuminate\Database\Seeder;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        // Scope Library Items
        $scopeItems = [
            // Design
            ['category' => 'Design', 'title' => 'UI/UX Design', 'description' => 'Custom user interface and user experience design tailored to your brand identity and target audience.', 'sort_order' => 1],
            ['category' => 'Design', 'title' => 'Wireframing & Prototyping', 'description' => 'Low and high-fidelity wireframes and interactive prototypes for stakeholder review and approval.', 'sort_order' => 2],
            ['category' => 'Design', 'title' => 'Responsive Design', 'description' => 'Fully responsive layouts optimized for desktop, tablet, and mobile devices.', 'sort_order' => 3],
            ['category' => 'Design', 'title' => 'Brand Identity Integration', 'description' => 'Integration of existing brand guidelines including colors, typography, and visual elements.', 'sort_order' => 4],
            ['category' => 'Design', 'title' => 'Design System Creation', 'description' => 'Component-based design system ensuring consistency across all pages and features.', 'sort_order' => 5],

            // Development
            ['category' => 'Development', 'title' => 'Frontend Development', 'description' => 'Modern frontend development using industry-standard frameworks and best practices.', 'sort_order' => 1],
            ['category' => 'Development', 'title' => 'Backend Development', 'description' => 'Server-side application development with secure APIs, database architecture, and business logic.', 'sort_order' => 2],
            ['category' => 'Development', 'title' => 'CMS Integration', 'description' => 'Content management system setup allowing easy content updates without developer assistance.', 'sort_order' => 3],
            ['category' => 'Development', 'title' => 'E-Commerce Integration', 'description' => 'Shopping cart, payment gateway integration, product management, and order processing.', 'sort_order' => 4],
            ['category' => 'Development', 'title' => 'Third-Party API Integration', 'description' => 'Integration with external services, APIs, and platforms as required by the project.', 'sort_order' => 5],
            ['category' => 'Development', 'title' => 'Database Design & Development', 'description' => 'Relational database architecture, optimization, and data migration services.', 'sort_order' => 6],
            ['category' => 'Development', 'title' => 'User Authentication & Authorization', 'description' => 'Secure login systems, role-based access control, and user management.', 'sort_order' => 7],

            // SEO
            ['category' => 'SEO', 'title' => 'On-Page SEO Optimization', 'description' => 'Meta tags, structured data, semantic HTML, and content optimization for search engines.', 'sort_order' => 1],
            ['category' => 'SEO', 'title' => 'Technical SEO Audit', 'description' => 'Comprehensive technical audit including site speed, crawlability, and indexing analysis.', 'sort_order' => 2],
            ['category' => 'SEO', 'title' => 'Analytics & Tracking Setup', 'description' => 'Google Analytics, Search Console, and conversion tracking configuration.', 'sort_order' => 3],
            ['category' => 'SEO', 'title' => 'Sitemap & Robots Configuration', 'description' => 'XML sitemap generation and robots.txt optimization for search engine crawling.', 'sort_order' => 4],

            // Hosting
            ['category' => 'Hosting', 'title' => 'Cloud Hosting Setup', 'description' => 'Cloud infrastructure provisioning, configuration, and deployment pipeline setup.', 'sort_order' => 1],
            ['category' => 'Hosting', 'title' => 'SSL Certificate Installation', 'description' => 'SSL/TLS certificate installation and HTTPS enforcement for secure browsing.', 'sort_order' => 2],
            ['category' => 'Hosting', 'title' => 'Domain Configuration', 'description' => 'DNS configuration, domain pointing, and email routing setup.', 'sort_order' => 3],
            ['category' => 'Hosting', 'title' => 'CDN Configuration', 'description' => 'Content delivery network setup for global performance optimization.', 'sort_order' => 4],

            // Content
            ['category' => 'Content', 'title' => 'Copywriting', 'description' => 'Professional copywriting for website pages, including headlines, body copy, and calls-to-action.', 'sort_order' => 1],
            ['category' => 'Content', 'title' => 'Content Migration', 'description' => 'Migration of existing content from old platform to new website with proper formatting.', 'sort_order' => 2],
            ['category' => 'Content', 'title' => 'Image Sourcing & Optimization', 'description' => 'Stock photography sourcing, image optimization, and responsive image implementation.', 'sort_order' => 3],

            // Maintenance
            ['category' => 'Maintenance', 'title' => 'Post-Launch Support', 'description' => '30-day post-launch support period for bug fixes and minor adjustments.', 'sort_order' => 1],
            ['category' => 'Maintenance', 'title' => 'Training & Documentation', 'description' => 'Admin training sessions and user documentation for content management.', 'sort_order' => 2],
            ['category' => 'Maintenance', 'title' => 'Ongoing Maintenance Plan', 'description' => 'Monthly maintenance including security updates, backups, and performance monitoring.', 'sort_order' => 3],
        ];

        foreach ($scopeItems as $item) {
            ScopeLibrary::create(array_merge($item, ['is_active' => true]));
        }

        // Service Library Items
        $serviceItems = [
            ['category' => 'Design', 'name' => 'UI/UX Design', 'default_price' => 3500.00, 'unit' => 'fixed', 'default_quantity' => 1, 'sort_order' => 1],
            ['category' => 'Design', 'name' => 'Wireframing & Prototyping', 'default_price' => 1500.00, 'unit' => 'fixed', 'default_quantity' => 1, 'sort_order' => 2],
            ['category' => 'Design', 'name' => 'Design System Creation', 'default_price' => 2500.00, 'unit' => 'fixed', 'default_quantity' => 1, 'sort_order' => 3],
            ['category' => 'Design', 'name' => 'Logo & Brand Design', 'default_price' => 2000.00, 'unit' => 'fixed', 'default_quantity' => 1, 'sort_order' => 4],

            ['category' => 'Development', 'name' => 'Frontend Development', 'default_price' => 5000.00, 'unit' => 'fixed', 'default_quantity' => 1, 'sort_order' => 1],
            ['category' => 'Development', 'name' => 'Backend Development', 'default_price' => 7500.00, 'unit' => 'fixed', 'default_quantity' => 1, 'sort_order' => 2],
            ['category' => 'Development', 'name' => 'Full-Stack Web Application', 'default_price' => 12000.00, 'unit' => 'fixed', 'default_quantity' => 1, 'sort_order' => 3],
            ['category' => 'Development', 'name' => 'CMS Integration', 'default_price' => 3000.00, 'unit' => 'fixed', 'default_quantity' => 1, 'sort_order' => 4],
            ['category' => 'Development', 'name' => 'E-Commerce Setup', 'default_price' => 8000.00, 'unit' => 'fixed', 'default_quantity' => 1, 'sort_order' => 5],
            ['category' => 'Development', 'name' => 'API Development', 'default_price' => 150.00, 'unit' => 'hourly', 'default_quantity' => 40, 'sort_order' => 6],

            ['category' => 'SEO', 'name' => 'SEO Audit & Optimization', 'default_price' => 2000.00, 'unit' => 'fixed', 'default_quantity' => 1, 'sort_order' => 1],
            ['category' => 'SEO', 'name' => 'Monthly SEO Management', 'default_price' => 1500.00, 'unit' => 'monthly', 'default_quantity' => 6, 'sort_order' => 2],
            ['category' => 'SEO', 'name' => 'Analytics Setup', 'default_price' => 500.00, 'unit' => 'fixed', 'default_quantity' => 1, 'sort_order' => 3],

            ['category' => 'Hosting', 'name' => 'Cloud Hosting Setup', 'default_price' => 1000.00, 'unit' => 'fixed', 'default_quantity' => 1, 'sort_order' => 1],
            ['category' => 'Hosting', 'name' => 'Managed Hosting (Monthly)', 'default_price' => 150.00, 'unit' => 'monthly', 'default_quantity' => 12, 'sort_order' => 2],
            ['category' => 'Hosting', 'name' => 'SSL & Security Setup', 'default_price' => 500.00, 'unit' => 'fixed', 'default_quantity' => 1, 'sort_order' => 3],

            ['category' => 'Maintenance', 'name' => 'Monthly Maintenance', 'default_price' => 500.00, 'unit' => 'monthly', 'default_quantity' => 12, 'sort_order' => 1],
            ['category' => 'Maintenance', 'name' => 'Training Session', 'default_price' => 150.00, 'unit' => 'hourly', 'default_quantity' => 2, 'sort_order' => 2],
            ['category' => 'Maintenance', 'name' => 'Post-Launch Support (30 days)', 'default_price' => 1500.00, 'unit' => 'fixed', 'default_quantity' => 1, 'sort_order' => 3],
        ];

        foreach ($serviceItems as $item) {
            ServiceLibrary::create(array_merge($item, ['is_active' => true]));
        }
    }
}
