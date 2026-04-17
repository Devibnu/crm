<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [];
        $companies = [
            ['name' => 'Jordan Stevenson', 'company' => 'Hall-Robbins PLC', 'email' => 'don85@johnson.com', 'country' => 'USA', 'address' => '7777 Mendez Plains', 'contact' => '(616) 865-4180'],
            ['name' => 'Richard Payne', 'company' => 'Gentry-Weiss', 'email' => 'braborez@shannon.com', 'country' => 'Canada', 'address' => '42 Annette Roads', 'contact' => '(254) 564-4289'],
            ['name' => 'Jennifer Summers', 'company' => 'Kiehn PLC', 'email' => 'jgsummers@powell.com', 'country' => 'UK', 'address' => '48 Prairie Avenue', 'contact' => '(847) 479-8926'],
            ['name' => 'Timothy Stevenson', 'company' => 'Spinka LLC', 'email' => 'jstevenson@anderson.com', 'country' => 'Germany', 'address' => '05 Murphy Vista', 'contact' => '(313) 564-9867'],
            ['name' => 'Virginia Saenz', 'company' => 'York LLC', 'email' => 'joella@fletcher.com', 'country' => 'France', 'address' => '554 Green Road', 'contact' => '(941) 489-0678'],
            ['name' => 'Kelly Smith', 'company' => 'Larson LLC', 'email' => 'ksmith@wright.net', 'country' => 'India', 'address' => '123 Main Street', 'contact' => '(555) 123-4567'],
            ['name' => 'Thomas Anderson', 'company' => 'Matrix Corp', 'email' => 'neo@matrix.com', 'country' => 'Australia', 'address' => '456 Oak Avenue', 'contact' => '(555) 987-6543'],
            ['name' => 'Sarah Connor', 'company' => 'Cyberdyne Systems', 'email' => 'sconnor@cyber.com', 'country' => 'USA', 'address' => '789 Tech Blvd', 'contact' => '(555) 246-8135'],
            ['name' => 'Bruce Wayne', 'company' => 'Wayne Enterprises', 'email' => 'bwayne@wayne.com', 'country' => 'USA', 'address' => '1 Wayne Tower', 'contact' => '(555) 369-2580'],
            ['name' => 'Diana Prince', 'company' => 'Themyscira Inc', 'email' => 'diana@themyscira.com', 'country' => 'Brazil', 'address' => '321 Embassy Row', 'contact' => '(555) 147-2583'],
        ];

        foreach ($companies as $c) {
            $clients[] = Client::create([
                'name' => $c['name'],
                'company' => $c['company'],
                'company_email' => $c['email'],
                'country' => $c['country'],
                'address' => $c['address'],
                'contact' => $c['contact'],
            ]);
        }

        $services = ['Software Development', 'UI/UX Design & Development', 'Unlimited Extended License', 'Template Customization'];
        $statuses = ['Paid', 'Downloaded', 'Sent', 'Draft', 'Partial Payment', 'Past Due'];

        for ($i = 0; $i < 50; $i++) {
            $total = rand(100, 5000) + rand(0, 99) / 100;
            $isPaid = rand(0, 1);

            Invoice::create([
                'client_id' => $clients[array_rand($clients)]->id,
                'issued_date' => now()->subDays(rand(1, 365))->format('Y-m-d'),
                'due_date' => now()->addDays(rand(1, 90))->format('Y-m-d'),
                'service' => $services[array_rand($services)],
                'total' => $total,
                'avatar' => '/images/avatars/avatar-'.rand(1, 8).'.png',
                'invoice_status' => $statuses[array_rand($statuses)],
                'balance' => $isPaid ? 0 : round($total * rand(20, 100) / 100, 2),
            ]);
        }
    }
}
