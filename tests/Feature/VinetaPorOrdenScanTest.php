<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Empleado;
use App\Models\Producto;
use App\Models\TipoEmpaque;
use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaPorOrden;
use App\Models\VinetaRegistro;
use Database\Seeders\VinetasPorOrdenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VinetaPorOrdenScanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new VinetasPorOrdenSeeder())->run();
    }

    public function test_vinetas_por_orden_existen_y_se_muestran_en_la_vista(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('vinetas-por-orden.index'));
        $response->assertOk();
        $response->assertSee('or-1');
        $response->assertSee('or-10');
        $response->assertSee('or-11');
        $response->assertSee('3606');
        $response->assertSee('111394');
        $response->assertSee('Cuban Rounds');
        $response->assertSee('The Edge Connecticut');
        $response->assertSee('Smoker Friendly');
        $response->assertSee('The Edge');
    }

    public function test_api_scan_detecta_codigo_qr_de_vineta_por_orden(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Escanear or-1
        $response = $this->postJson('/api/vinetas/scan', [
            'qr' => 'or-1',
        ]);

        $response->assertOk();
        $response->assertJsonPath('vineta.api_id', 1);
        $response->assertJsonPath('vineta.codigo_qr', 'or-1');
        $response->assertJsonPath('vineta.id_pendiente_empaque', 'or-1');
        $response->assertJsonPath('vineta.item', '151998');
        $response->assertJsonPath('vineta.codigo_producto', 'P-01942');
        $response->assertJsonPath('vineta.marca', 'Cuban Rounds');
        $response->assertJsonPath('vineta.nombre', 'Robusto');
        $response->assertJsonPath('vineta.es_por_orden', true);
        $response->assertJsonPath('vineta.proceso.puede_llenar', true);
    }

    public function test_api_scan_detecta_or_11_con_os_3606_y_oc_111394(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/vinetas/scan', [
            'qr' => 'or-11',
        ]);

        $response->assertOk();
        $response->assertJsonPath('vineta.api_id', 11);
        $response->assertJsonPath('vineta.codigo_qr', 'or-11');
        $response->assertJsonPath('vineta.item', '151997');
        $response->assertJsonPath('vineta.codigo_producto', 'P-01947');
        $response->assertJsonPath('vineta.orden_del_sistema', '3606');
        $response->assertJsonPath('vineta.orden', '111394');
        $response->assertJsonPath('vineta.marca', 'Cuban Rounds');
        $response->assertJsonPath('vineta.nombre', 'Toro');
        $response->assertJsonPath('vineta.capa', 'INDONESIA');
        $response->assertJsonPath('vineta.vitola', '6-1/8X50');
        $response->assertJsonPath('vineta.tipo_empaque', 'Display/24');
        $response->assertJsonPath('vineta.mes', 'MAYO 2026');
        $response->assertJsonPath('vineta.es_por_orden', true);
    }

    public function test_api_scan_detecta_qr_case_insensitive(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Escanear OR-5 en mayúsculas
        $response = $this->postJson('/api/vinetas/scan', [
            'qr' => 'OR-5',
        ]);

        $response->assertOk();
        $response->assertJsonPath('vineta.id_pendiente_empaque', 'or-5');
        $response->assertJsonPath('vineta.item', '151962');
        $response->assertJsonPath('vineta.codigo_producto', 'P-15236');
    }

    public function test_api_registros_guarda_multiples_escaneos_con_id_autoincrementable_o_n(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $empleado = Empleado::create([
            'codigo' => 'EMP-100',
            'nombre' => 'Carlos López',
            'activo' => true,
        ]);

        $actividad = Actividad::create([
            'api_id_actividad' => 991,
            'codigo_actividad' => 'ACT-01',
            'nombre' => 'Rezagado',
            'activo' => true,
        ]);

        // 1er escaneo y guardado de or-1
        $scan1 = $this->postJson('/api/vinetas/scan', ['qr' => 'or-1']);
        $scan1->assertOk();
        $vinetaId1 = $scan1->json('vineta.id');

        $store1 = $this->postJson('/api/vineta-registros', [
            'vineta_id' => $vinetaId1,
            'empleado_codigo' => 'EMP-100',
            'actividad_id' => $actividad->id,
            'cantidad_puros' => 100,
            'fecha_registro' => '2026-08-26',
            'hora_registro' => '08:00',
        ]);

        $store1->assertCreated();
        $store1->assertJsonPath('registro.codigo_vineta', 'o-1');

        // 2do escaneo y guardado de la MISMA viñeta or-1 (mismo día y misma actividad)
        $store2 = $this->postJson('/api/vineta-registros', [
            'vineta_id' => $vinetaId1,
            'empleado_codigo' => 'EMP-100',
            'actividad_id' => $actividad->id,
            'cantidad_puros' => 150,
            'fecha_registro' => '2026-08-26',
            'hora_registro' => '09:00',
        ]);

        $store2->assertCreated();
        $store2->assertJsonPath('registro.codigo_vineta', 'o-2');

        // 3er escaneo de or-2
        $scan2 = $this->postJson('/api/vinetas/scan', ['qr' => 'or-2']);
        $scan2->assertOk();
        $vinetaId2 = $scan2->json('vineta.id');

        $store3 = $this->postJson('/api/vineta-registros', [
            'vineta_id' => $vinetaId2,
            'empleado_codigo' => 'EMP-100',
            'actividad_id' => $actividad->id,
            'cantidad_puros' => 200,
            'fecha_registro' => '2026-08-26',
            'hora_registro' => '10:00',
        ]);

        $store3->assertCreated();
        $store3->assertJsonPath('registro.codigo_vineta', 'o-3');

        // Verificar que en la vista web de Viñetas Registradas aparezcan los badges ID o-1, ID o-2, ID o-3
        $webResponse = $this->actingAs($user)->get(route('vineta-registros.index', ['fecha' => '2026-08-26']));
        $webResponse->assertOk();
        $webResponse->assertSee('ID o-1');
        $webResponse->assertSee('ID o-2');
        $webResponse->assertSee('ID o-3');
    }
}
