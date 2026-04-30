<?php

namespace Database\Seeders;

use App\Models\ProjectReference;
use Illuminate\Database\Seeder;

class ProjectReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $references = [
            [
                'project_name' => 'LUMA Citizen Management Portal',
                'project_location' => 'Town of Bridgewater, VA',
                'project_description' => "Developed a custom citizen management portal to allow town staff to better track citizens, households, issues and related data including phone calls which are tracked via API integration with the Town's VOIP service. Also created a 'parser' email bot where town staff can forward citizen emails to a dedicated address that will parse the issue data and automatically assign it to the citizen/household record.",
                'year_completed' => 2026,
                'name' => 'Jay Litten',
                'title' => 'Town Administrator',
                'company' => 'Town of Bridgewater',
                'email' => 'jjlitten@bridgewater.town',
                'phone' => '(540) 908-4212',
                'sort_order' => 1,
            ],
            [
                'project_name' => 'PromoSoft',
                'project_location' => 'Richmond, VA',
                'project_description' => 'Created comprehensive, custom SaaS software to serve the promotional products industry by developing a process + product to help companies manage the order fulfillment process from sourcing to production to fulfillment, including customer/order/quote management, online payments, sales commission tracking/reporting, shipping rate/label integrations with the major providers, Quickbooks API integration, PromoStandards API integration, ShipStation Integration etc.',
                'year_completed' => 2026,
                'name' => 'Doug Mays',
                'title' => 'Co-Founder',
                'company' => 'PromoSoft',
                'email' => 'doug@thepromosoft.com',
                'phone' => '804-798-7677',
                'sort_order' => 2,
            ],
            [
                'project_name' => 'Performance Pickleball',
                'project_location' => 'Henrico, VA',
                'project_description' => "Development custom 'country club' style software to assist with member management of an indoor pickleball facility including a native iOs/Android app for members to connect with each other, book court time, see their purchase activity, pay invoices, manage their profile etc. including a full administration system for staff to manage all member data and integration with the Clover PoS system to pull in Member restaurant + merchandise purchase activity for recurring monthly billing.",
                'year_completed' => 2025,
                'name' => 'Jon Laaser',
                'title' => 'Founder',
                'company' => 'Performance Pickleball',
                'email' => 'jon@ppbrva.com',
                'phone' => '804-997-4964',
                'sort_order' => 3,
            ],
            [
                'project_name' => 'Agency Management Software (AMS)',
                'project_location' => 'Henrico, VA',
                'project_description' => 'Created custom SaaS solution for a digital marketing agency that provides both an administrative portal to manage all project, service, and customer related data, but also a customer portal where users can view project status, provide feedback, approvals, obtain assets, make payments online and more. Since the agency offers video production, graphic design, and SEO services specific views were built to support their process in how they prepare and deliver those assets to customers, including AI automation where applicable.',
                'year_completed' => 2026,
                'name' => 'Ryan Leach',
                'title' => 'COO',
                'company' => 'Fresh Move Media',
                'email' => 'ryan@freshmovemedia.com',
                'phone' => '(804) 214-6093',
                'sort_order' => 4,
            ],
            [
                'project_name' => 'County Line Collision',
                'project_location' => 'Powhatan, VA',
                'project_description' => "Created custom SaaS solution for an auto collision repair shop to transform their paper based system into a digital system including a production board designed to be broadcast on a large screen in the shop to show all repairs in progress, who is assigned, what state they are in, etc. This same information is communicated to the customer via their 'tracker' page that provides updates and notifications as their vehicle moves through the repair process.",
                'year_completed' => 2026,
                'name' => "Bryant 'Bubba' Smith",
                'title' => 'Owner',
                'company' => 'County Line Collision',
                'email' => 'bubba76ccc@gmail.com',
                'phone' => '804-598-3738',
                'sort_order' => 5,
            ],
        ];

        foreach ($references as $ref) {
            ProjectReference::updateOrCreate(
                ['project_name' => $ref['project_name']],
                array_merge($ref, ['is_active' => true]),
            );
        }
    }
}
