<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Node;
use App\Models\VaultPlan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed high-tech infrastructure nodes
        Node::create([
            'name' => 'Nœud Serveur Alpha',
            'amount' => 5000.00,
            'generation_profit' => 250.00,
            'technology_level' => 1,
            'duration' => 30,
            'stock_quantity' => 100,
            'limited_purchase_count' => 5,
        ]);

        Node::create([
            'name' => 'Nœud Infrastructure Bêta',
            'amount' => 15000.00,
            'generation_profit' => 800.00,
            'technology_level' => 2,
            'duration' => 30,
            'stock_quantity' => 50,
            'limited_purchase_count' => 3,
        ]);

        Node::create([
            'name' => 'Nœud Supercalculateur Gamma',
            'amount' => 50000.00,
            'generation_profit' => 3000.00,
            'technology_level' => 3,
            'duration' => 30,
            'stock_quantity' => 25,
            'limited_purchase_count' => 2,
        ]);

        Node::create([
            'name' => 'Nœud Réseau Delta',
            'amount' => 150000.00,
            'generation_profit' => 10000.00,
            'technology_level' => 4,
            'duration' => 30,
            'stock_quantity' => 10,
            'limited_purchase_count' => 1,
        ]);

        Node::create([
            'name' => 'Nœud Cloud Oméga',
            'amount' => 500000.00,
            'generation_profit' => 38000.00,
            'technology_level' => 5,
            'duration' => 30,
            'stock_quantity' => 5,
            'limited_purchase_count' => 1,
        ]);

        // Seed Vault plans
        VaultPlan::create([
            'name' => 'Coffre Épargne Standard',
            'fixed_investment_amount' => 10000.00,
            'fixed_return' => 1.14,
            'profit_amount' => 1400.00,
            'duration' => 7,
        ]);

        VaultPlan::create([
            'name' => 'Coffre Rendement Avancé',
            'fixed_investment_amount' => 50000.00,
            'fixed_return' => 1.22,
            'profit_amount' => 11250.00,
            'duration' => 15,
        ]);

        VaultPlan::create([
            'name' => 'Coffre Premium Croissance',
            'fixed_investment_amount' => 200000.00,
            'fixed_return' => 1.36,
            'profit_amount' => 72000.00,
            'duration' => 30,
        ]);

        // Seed AVIP Products
        \App\Models\AVIPProduct::create([
            'name' => 'Accélérateur AVIP 1',
            'description' => 'Unité de calcul quantique ultra-rapide.',
            'amount' => 30000.00,
            'daily_salary' => 800.00,
            'required_vip_level' => 1,
            'avip_level' => 1,
            'image' => 'https://images.unsplash.com/photo-1591453089816-0fbb971b454c?auto=format&fit=crop&w=600&q=80',
            'active' => true,
        ]);

        \App\Models\AVIPProduct::create([
            'name' => 'Accélérateur AVIP 2',
            'description' => 'Grille de neurones profonds synaptique.',
            'amount' => 75000.00,
            'daily_salary' => 2200.00,
            'required_vip_level' => 2,
            'avip_level' => 2,
            'image' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=600&q=80',
            'active' => true,
        ]);

        \App\Models\AVIPProduct::create([
            'name' => 'Accélérateur AVIP 3',
            'description' => 'Unité de traitement neuronal Overlord.',
            'amount' => 180000.00,
            'daily_salary' => 6000.00,
            'required_vip_level' => 3,
            'avip_level' => 3,
            'image' => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=600&q=80',
            'active' => true,
        ]);

        // Create the pre-funded test user as an admin
        User::factory()->create([
            'phone' => '1234567890',
            'balance' => 250000.00,
            'referral_code' => 'ARM2026',
            'role' => 'admin',
            'draw_spins' => 20,
        ]);

        // Create the custom admin user
        User::updateOrCreate(
            ['phone' => '691051864'],
            [
                'balance' => 250000.00,
                'referral_code' => 'ARM691',
                'role' => 'admin',
                'draw_spins' => 50,
                'password' => \Illuminate\Support\Facades\Hash::make('admin2026'),
            ]
        );

        // Seed dynamic high-tech news announcements
        \App\Models\Announcement::create([
            'title' => 'Déploiement des puces IA ARM Neoverse V3',
            'content' => 'ARM Holding annonce l\'intégration mondiale de sa nouvelle architecture Neoverse V3 au cœur des plus grands supercalculateurs d\'IA. Ces puces offrent une efficacité énergétique inédite pour le traitement de modèles de langage (LLM) de plus de 100 milliards de paramètres.',
            'image_url' => '/images/omega_quantum.jpg',
            'link' => '/nodes',
            'active' => true,
        ]);

        \App\Models\Announcement::create([
            'title' => 'Nouveau Serveur ARM Helios AI pour Data Centers',
            'content' => 'Conçu spécifiquement pour le traitement massif en temps réel des réseaux de neurones profonds, le serveur Helios AI intègre 4 puces accélératrices ARM d\'inférence directe. Ce système révolutionne les performances de calcul pour les entreprises cloud partenaires.',
            'image_url' => '/images/partnership.jpg',
            'link' => '/wallet',
            'active' => true,
        ]);

        \App\Models\Announcement::create([
            'title' => 'Cortex-X95 : L\'IA générative directement sur puces ARM',
            'content' => 'Le nouveau processeur ARM Cortex-X95 intègre un moteur neuronal (NPU) de dernière génération permettant l\'exécution locale ultra-rapide d\'algorithmes d\'IA générative. Une avancée majeure pour les terminaux Edge connectés au réseau global d\'ARM Holding.',
            'image_url' => '/images/vip_upgrade.jpg',
            'link' => '/vip',
            'active' => true,
        ]);

        \App\Models\Announcement::create([
            'title' => 'Inauguration du Quantum Grid ARM à Singapour',
            'content' => 'Le consortium ARM Secure Net vient de déployer un cluster de calcul hybride silicium-quantique à Singapour. Cette infrastructure permet le co-traitement distribué ultra-sécurisé via cryptographie post-quantique.',
            'image_url' => '/images/omega_quantum.jpg',
            'link' => '/nodes',
            'active' => true,
        ]);

        \App\Models\Announcement::create([
            'title' => 'Refroidissement Cryogénique Actif sur Nœuds Oméga',
            'content' => 'Afin d\'accompagner les records de calcul de l\'IA générative, ARM Holding déploie des systèmes de refroidissement cryogénique liquide à base de micro-canaux directs.',
            'image_url' => '/images/partnership.jpg',
            'link' => '/nodes',
            'active' => true,
        ]);

        \App\Models\Announcement::create([
            'title' => 'Partenariat Stratégique ARM & NVIDIA Blackwell',
            'content' => 'Une alliance technologique majeure unit désormais ARM Holding aux processeurs graphiques de nouvelle génération Blackwell pour concevoir la plateforme ultime de co-traitement.',
            'image_url' => '/images/vip_upgrade.jpg',
            'link' => '/vip',
            'active' => true,
        ]);
    }
}
