<?php

namespace Database\Seeders;

use App\Models\Mission;
use App\Models\User;
use App\Models\Avance;
use App\Models\Activity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class MissionSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Récupération des acteurs (matricules venant de ton UserSeeder)
        $raf  = User::where('matricule', 'RAF-001')->firstOrFail();
        $cp   = User::where('matricule', 'CP-001')->firstOrFail();
        $accp = User::where('matricule', 'ACCP-001')->firstOrFail();

        // ✅ Missionnaires (via rôle)
        $missionnaires = User::whereHas('roles', function ($q) {
            $q->where('name', 'missionnaire');
        })->orderBy('id')->get();

        if ($missionnaires->isEmpty()) {
            throw new \RuntimeException("Aucun missionnaire trouvé. Lance d'abord UserSeeder.");
        }

        $today = Carbon::today();

        // ✅ Paramètres par statut (dates cohérentes)
        $plan = [
            'brouillon'      => ['start' => 14,  'duration' => 2],
            'en_attente_ch'  => ['start' => 10,  'duration' => 2],
            'valide_ch'      => ['start' => 8,   'duration' => 2],
            'valide_raf'     => ['start' => 6,   'duration' => 2],
            'valide_cp'      => ['start' => 4,   'duration' => 2],
            'avance_payee'   => ['start' => 2,   'duration' => 3],
            'en_cours'       => ['start' => 0,   'duration' => 5],
            'cloturee'       => ['start' => -10, 'duration' => 3],
        ];

        $destinations = ['Morondava', 'Mahajanga', 'Antsirabe', 'Toliara', 'Tamatave', 'Fianarantsoa', 'Ambositra', 'Antananarivo'];
        $moyens = ['4x4', 'Taxi-brousse', 'Avion', 'Moto', 'Bateau'];

        $perStatus = 2; // ✅ "à chaque statut il y en a" => 2 missions par statut
        $globalIndex = 0;

        foreach ($plan as $status => $cfg) {
            for ($k = 1; $k <= $perStatus; $k++) {
                $demandeur = $missionnaires[$globalIndex % $missionnaires->count()];

                // CH = chef du missionnaire (si jamais null, fallback sur CH-001)
                $fallbackCH = User::where('matricule', 'CH-001')->first();
                $chId = $demandeur->chef_hierarchique_id ?: ($fallbackCH?->id);

                $start = $today->copy()->addDays($cfg['start']);
                $end   = $start->copy()->addDays($cfg['duration']);

                $montant = 150000 + ($globalIndex * 25000); // montants variés

                $keyObjet = "SEED - {$status} - " . str_pad((string)(($globalIndex % 99) + 1), 2, '0', STR_PAD_LEFT);

                // ✅ Base mission
                $data = [
                    'demandeur_id'            => $demandeur->id,
                    'validation_ch_id'        => null,
                    'validation_raf_id'       => null,
                    'validation_cp_id'        => null,

                    'objet'                   => $keyObjet,
                    'destination'             => $destinations[$globalIndex % count($destinations)],
                    'moyen_deplacement'       => $moyens[$globalIndex % count($moyens)],
                    'date_debut'              => $start->toDateString(),
                    'date_fin'                => $end->toDateString(),

                    'montant_avance_demande'  => $montant,
                    'montant_total_justifie'  => null,
                    'reliquat_a_rembourser'   => null,

                    'statut_actuel'           => $status,
                    'date_echeance_audit'     => $end->copy()->addDays(7),
                    'date_regularisation'     => null,
                ];

                // ✅ Remplissage des validateurs selon statut
                if (in_array($status, ['valide_ch','valide_raf','valide_cp','avance_payee','en_cours','cloturee'], true)) {
                    $data['validation_ch_id'] = $chId;
                }
                if (in_array($status, ['valide_raf','valide_cp','avance_payee','en_cours','cloturee'], true)) {
                    $data['validation_raf_id'] = $raf->id;
                }
                if (in_array($status, ['valide_cp','avance_payee','en_cours','cloturee'], true)) {
                    $data['validation_cp_id'] = $cp->id;
                }

                // ✅ Clôture: on met des valeurs financières cohérentes
                if ($status === 'cloturee') {
                    $totalJustifie = $montant - 20000; // ex: il reste 20k à rembourser
                    $data['montant_total_justifie'] = $totalJustifie;
                    $data['reliquat_a_rembourser'] = max(0, $montant - $totalJustifie);
                    $data['date_regularisation'] = now();
                }

                // ✅ Upsert mission (évite doublons si tu relances les seeders)
                $mission = Mission::updateOrCreate(
                    ['objet' => $keyObjet],
                    $data
                );

                // ✅ Si avance_payee / en_cours / cloturee => créer un paiement d'avance
                if (in_array($status, ['avance_payee','en_cours','cloturee'], true)) {
                    Avance::updateOrCreate(
                        [
                            'mission_id'      => $mission->id,
                            'type_operation'  => 'paiement',
                        ],
                        [
                            'executed_by_id'       => $accp->id,
                            'montant'              => $montant,
                            'date_operation'       => now(),
                            'numero_piece_paiement'=> "SEED-PAY-" . str_pad((string)$mission->id, 4, '0', STR_PAD_LEFT),
                        ]
                    );
                }

                // ✅ (Optionnel mais utile) petite traçabilité Activity
                $this->seedActivities($mission, $demandeur->id, $chId, $raf->id, $cp->id, $accp->id);

                $globalIndex++;
            }
        }
    }

    private function seedActivities(Mission $mission, int $demandeurId, ?int $chId, int $rafId, int $cpId, int $accpId): void
    {
        // On met juste ce qui correspond au statut actuel (simple et lisible)
        $status = $mission->statut_actuel;

        // soumission si pas brouillon
        if ($status !== 'brouillon') {
            Activity::firstOrCreate(
                ['mission_id' => $mission->id, 'action_type' => 'soumission', 'performed_by_id' => $demandeurId],
                ['description' => "Soumission de la mission ({$status})."]
            );
        }

        if (in_array($status, ['valide_ch','valide_raf','valide_cp','avance_payee','en_cours','cloturee'], true) && $chId) {
            Activity::firstOrCreate(
                ['mission_id' => $mission->id, 'action_type' => 'validation_ch', 'performed_by_id' => $chId],
                ['description' => "Validation CH enregistrée."]
            );
        }

        if (in_array($status, ['valide_raf','valide_cp','avance_payee','en_cours','cloturee'], true)) {
            Activity::firstOrCreate(
                ['mission_id' => $mission->id, 'action_type' => 'validation_raf', 'performed_by_id' => $rafId],
                ['description' => "Validation RAF enregistrée."]
            );
        }

        if (in_array($status, ['valide_cp','avance_payee','en_cours','cloturee'], true)) {
            Activity::firstOrCreate(
                ['mission_id' => $mission->id, 'action_type' => 'validation_cp', 'performed_by_id' => $cpId],
                ['description' => "Validation CP enregistrée."]
            );
        }

        if (in_array($status, ['avance_payee','en_cours','cloturee'], true)) {
            Activity::firstOrCreate(
                ['mission_id' => $mission->id, 'action_type' => 'paiement_avance', 'performed_by_id' => $accpId],
                ['description' => "Paiement d'avance enregistré (seed)."]
            );
        }

        if ($status === 'cloturee') {
            Activity::firstOrCreate(
                ['mission_id' => $mission->id, 'action_type' => 'cloture', 'performed_by_id' => $accpId],
                ['description' => "Mission clôturée (seed)."]
            );
        }
    }
}
