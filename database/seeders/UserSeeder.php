<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private function upsertUser(array $data, array $roleNames): User
    {
        // ✅ Mot de passe UNIQUE pour tous
        $plain = 'password';

        $user = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'matricule'            => $data['matricule'],
                'nom'                  => $data['nom'],
                'name'                 => $data['name'],
                'telephone'            => $data['telephone'] ?? null,
                'unite'                => $data['unite'] ?? null,
                'poste'                => $data['poste'] ?? null,
                'chef_hierarchique_id' => $data['chef_hierarchique_id'] ?? null,
                'status'               => $data['status'] ?? 'active',

                // ✅ FORCÉ: "password" pour tout le monde
                'password'             => Hash::make($plain),
            ]
        );

        $roleIds = Role::whereIn('name', $roleNames)->pluck('id')->all();
        $user->roles()->sync($roleIds);

        return $user;
    }

    public function run(): void
    {
        // ✅ ADMIN
        $admin = $this->upsertUser([
            'matricule' => 'AD2M-ADMIN',
            'nom'       => 'Administrateur',
            'name'      => 'Admin AD2M',
            'email'     => 'admin@ad2m.com',
            'telephone' => null,
            'unite'     => 'Direction',
            'poste'     => 'Administrateur',
            'chef_hierarchique_id' => null,
        ], ['administrateur','admin']);

        /**
         * ✅ 3 CH (vrais noms)
         */
        $ch1 = $this->upsertUser([
            'matricule' => 'CH-001',
            'nom'       => 'RAZAFIMAHEFA',
            'name'      => 'RAZAFIMAHEFA Claudio',
            'email'     => 'razafimahefa.claudio@ad2m.local',
            'telephone' => null,
            'unite'     => 'UGP',
            'poste'     => 'AF',
            'chef_hierarchique_id' => null,
        ], ['chef_hierarchique']);

        $ch2 = $this->upsertUser([
            'matricule' => 'CH-002',
            'nom'       => 'ANDRIANATONADRO',
            'name'      => 'ANDRIANATONADRO Jean Maximnin',
            'email'     => 'andrianatonadro.jean.maximnin@ad2m.local',
            'telephone' => null,
            'unite'     => 'UGP',
            'poste'     => 'CAOP SF',
            'chef_hierarchique_id' => null,
        ], ['chef_hierarchique']);

        $ch3 = $this->upsertUser([
            'matricule' => 'CH-003',
            'nom'       => 'RAFAMANTANANTSOA',
            'name'      => 'RAFAMANTANANTSOA Romuald',
            'email'     => 'rafamantanantsoa.romuald@ad2m.local',
            'telephone' => null,
            'unite'     => 'UGP',
            'poste'     => 'APM',
            'chef_hierarchique_id' => null,
        ], ['chef_hierarchique']);

        // ✅ RAF
        $raf = $this->upsertUser([
            'matricule' => 'RAF-001',
            'nom'       => 'HAINGONOMENJANAHARY',
            'name'      => 'HAINGONOMENJANAHARY Eliarisoa',
            'email'     => 'haingonomenjanahary.eliarisoa@ad2m.local',
            'telephone' => null,
            'unite'     => 'Administration et Finances',
            'poste'     => 'RAF',
            'chef_hierarchique_id' => null,
        ], ['raf']);

        // ✅ CP
        $cp = $this->upsertUser([
            'matricule' => 'CP-001',
            'nom'       => 'RAFIDINARIVO',
            'name'      => 'RAFIDINARIVO Haja Joel',
            'email'     => 'rafidinarivo.haja.joel@ad2m.local',
            'telephone' => null,
            'unite'     => 'UGP',
            'poste'     => 'CP',
            'chef_hierarchique_id' => null,
        ], ['coordonnateur_de_projet']);

        // ✅ ACCP
        $accp = $this->upsertUser([
            'matricule' => 'ACCP-001',
            'nom'       => 'RANDRIANINDRINA',
            'name'      => 'RANDRIANINDRINA Henintsoa Tahiriniaina',
            'email'     => 'randrianindrina.henintsoa.tahiriniaina@ad2m.local',
            'telephone' => null,
            'unite'     => 'Administration et Finances',
            'poste'     => 'ACCP',
            'chef_hierarchique_id' => null,
        ], ['accp']);

        // ✅ AUTRES MISSIONNAIRES
        $chs = [$ch1->id, $ch2->id, $ch3->id];
        $i = 0;

        $missionnaires = [
            ['mat' => 'M-001', 'nom' => 'RAKOTOARISOA',    'name' => 'RAKOTOARISOA Doris',                        'poste' => 'CMVA', 'email' => 'rakotoarisoa.doris@ad2m.local'],
            ['mat' => 'M-002', 'nom' => 'RAFANOMEZANTSOA', 'name' => 'RAFANOMEZANTSOA Erick Alain',              'poste' => 'SO',   'email' => 'rafanomezantsoa.erick.alain@ad2m.local'],
            ['mat' => 'M-003', 'nom' => 'RESAMPA',         'name' => 'RESAMPA Olivia',                           'poste' => 'AADM', 'email' => 'resampa.olivia@ad2m.local'],
            ['mat' => 'M-004', 'nom' => 'RALAMBOMANANA',   'name' => 'RALAMBOMANANA Herimampionona',            'poste' => 'TECHNICIEN', 'email' => 'ralambomanana.herimampionona@ad2m.local'],
            ['mat' => 'M-005', 'nom' => 'RAKOTONIAINA',    'name' => 'RAKOTONIAINA Andrianjaka',                'poste' => 'IPIR', 'email' => 'rakotoniaina.andrianjaka@ad2m.local'],
            ['mat' => 'M-006', 'nom' => 'RABEARIVONY',     'name' => 'RABEARIVONY Harimihaja Tantely Sébastien', 'poste' => 'AMVA', 'email' => 'rabearivony.harimihaja.tantely.sebastien@ad2m.local'],
            ['mat' => 'M-007', 'nom' => 'ANDRIAMAHATASY',  'name' => 'ANDRIAMAHATASY Victor',                   'poste' => 'AF',   'email' => 'andriamahatasy.victor@ad2m.local'],
            ['mat' => 'M-008', 'nom' => 'ANDRIANTSILAVO',  'name' => 'ANDRIANTSILAVO Manoamanana',              'poste' => 'CRP',  'email' => 'andriantsilavo.manoamanana@ad2m.local'],
            ['mat' => 'M-009', 'nom' => 'RAZAFINDRATSIMA', 'name' => 'RAZAFINDRATSIMA Alain',                   'poste' => 'RSE',  'email' => 'razafindratsima.alain@ad2m.local'],
            ['mat' => 'M-010', 'nom' => 'RAKOTONDRASOA',   'name' => 'RAKOTONDRASOA Lalaina',                   'poste' => 'Informaticien', 'email' => 'rakotondrasoa.lalaina@ad2m.local'],
            ['mat' => 'M-011', 'nom' => 'RAKOTONDRANAIVO', 'name' => 'RAKOTONDRANAIVO Pierre Célestin',         'poste' => 'CSLT en Commercialisation', 'email' => 'rakotondranaivo.pierre.celestin@ad2m.local'],
        ];

        foreach ($missionnaires as $m) {
            $this->upsertUser([
                'matricule' => $m['mat'],
                'nom'       => $m['nom'],
                'name'      => $m['name'],
                'email'     => $m['email'],
                'telephone' => null,
                'unite'     => 'UGP',
                'poste'     => $m['poste'],
                'chef_hierarchique_id' => $chs[$i % count($chs)],
            ], ['missionnaire']);

            $i++;
        }
    }
}
