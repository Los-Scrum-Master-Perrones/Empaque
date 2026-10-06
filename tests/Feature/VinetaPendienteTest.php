<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Empleado;
use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VinetaPendienteTest extends TestCase
{
    use RefreshDatabase;

    private function mockPendientesApi(): void
    {
        Cache::forget('vinetas_pendientes:api_data:v1');

        Http::fake([
            'http://192.168.2.7:8080/api/pendiente/empaque/listar*' => Http::response([
                'data' => [
                    [
                        'id_pendiente' => 14741,
                        'id_categoria' => 4,
                        'categoria' => 'WAREHOUSE',
                        'mes' => 'ABRIL 2024',
                        'item' => '00110450',
                        'orden' => 'INT-H-1392',
                        'orden_del_sitema' => '3434',
                        'observacion' => '',
                        'presentacion' => 'Puros Tripa Larga',
                        'marca' => 'Les Privatiers Limitada 2024',
                        'vitola' => '4X60',
                        'nombre' => 'Robusto Especial',
                        'capa' => 'Cameroon',
                        'anillo' => 'SI',
                        'cello' => 'SI',
                        'upc' => 'NO',
                        'pendiente' => 10000,
                        'saldo' => 8500,
                        'tipo_empaque' => 'MAZOS 1/10',
                        'por_caja' => 10,
                        'codigo_productos' => 'P-23994',
                    ],
                    [
                        'id_pendiente' => 18096,
                        'id_categoria' => 1,
                        'categoria' => 'NEW ROLL',
                        'mes' => 'MARZO 2025',
                        'item' => '00904099',
                        'orden' => 'HON-4144',
                        'orden_del_sitema' => '3496',
                        'observacion' => null,
                        'presentacion' => 'Puros Tripa Larga',
                        'marca' => 'The Edge Sampler',
                        'vitola' => '5-1/2X50',
                        'nombre' => 'Robusto Tubo',
                        'capa' => 'Maduro',
                        'anillo' => 'SI',
                        'cello' => 'SI',
                        'upc' => 'NO',
                        'pendiente' => 4500,
                        'saldo' => 900,
                        'tipo_empaque' => 'Display 1/15',
                        'por_caja' => 60,
                        'codigo_productos' => 'P-02005',
                    ],
                ],
                'total' => 2,
                'estatus' => 200,
            ], 200),
        ]);
    }

    public function test_usuario_autenticado_puede_ver_vista_vinetas_pendientes(): void
    {
        $this->mockPendientesApi();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('vinetas-pendientes.index'));

        $response->assertOk();
        $response->assertSee('Viñetas pendientes');
        $response->assertSee('#1');
        $response->assertSee('#2');
        $response->assertSee('li-1');
        $response->assertSee('li-2');
        $response->assertSee('btn-ver-qr');
        $response->assertSee('data-qr-code="li-1"', false);
        $response->assertSee('data-qr-code="li-2"', false);
        $response->assertSee('vinetaQrModal');
        $response->assertSee('Les Privatiers Limitada 2024');
        $response->assertSee('The Edge Sampler');
        $response->assertSee('3434');
        $response->assertSee('INT-H-1392');
        $response->assertSee('P-23994');
        $response->assertSee('8,500');
    }

    public function test_filtros_de_vinetas_pendientes(): void
    {
        $this->mockPendientesApi();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('vinetas-pendientes.index', [
            'marca' => 'Privatiers',
        ]));

        $response->assertOk();
        $response->assertSee('Les Privatiers Limitada 2024');
        $response->assertDontSee('The Edge Sampler');

        $responseFilterOs = $this->actingAs($user)->get(route('vinetas-pendientes.index', [
            'orden_del_sistema' => '3496',
        ]));

        $responseFilterOs->assertOk();
        $responseFilterOs->assertSee('The Edge Sampler');
        $responseFilterOs->assertDontSee('Les Privatiers Limitada 2024');
    }

    public function test_peticion_ajax_retorna_solo_la_tabla_parcial(): void
    {
        $this->mockPendientesApi();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('vinetas-pendientes.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertSee('vinetasPendientesTableInner');
        $response->assertDontSee('<!DOCTYPE html>');
    }

    public function test_api_scan_detecta_codigo_qr_de_vineta_pendiente(): void
    {
        $this->mockPendientesApi();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Escanear li-1
        $response = $this->postJson('/api/vinetas/scan', [
            'qr' => 'li-1',
        ]);

        $response->assertOk();
        $response->assertJsonPath('vineta.codigo_qr', 'li-1');
        $response->assertJsonPath('vineta.id_pendiente_empaque', 'li-1');
        $response->assertJsonPath('vineta.item', '00110450');
        $response->assertJsonPath('vineta.codigo_producto', 'P-23994');
        $response->assertJsonPath('vineta.orden_del_sistema', '3434');
        $response->assertJsonPath('vineta.orden', 'INT-H-1392');
        $response->assertJsonPath('vineta.marca', 'Les Privatiers Limitada 2024');
        $response->assertJsonPath('vineta.nombre', 'Robusto Especial');
        $response->assertJsonPath('vineta.capa', 'Cameroon');
        $response->assertJsonPath('vineta.cantidad_puros', 8500);
        $response->assertJsonPath('vineta.es_por_orden', true);
        $response->assertJsonPath('vineta.proceso.puede_llenar', true);
    }

    public function test_guardar_registro_de_limpieza_sobre_vineta_pendiente_asigna_codigo_l_secuencial(): void
    {
        $this->mockPendientesApi();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // 1. Escanear li-1 para que se cree/consulte la viñeta
        $scan = $this->postJson('/api/vinetas/scan', ['qr' => 'li-1']);
        $scan->assertOk();
        $vinetaId = $scan->json('vineta.id');
        $this->assertNotNull($vinetaId);

        $empleado = Empleado::create([
            'codigo' => 'EMP-LIMP-1',
            'nombre' => 'María Limpiadora',
            'cargo' => 'Limpia Puros',
            'activo' => true,
        ]);

        $actividadLimpieza = Actividad::create([
            'api_id_actividad' => 103,
            'codigo_actividad' => 'ACT-103',
            'nombre' => 'Limpieza de puros',
            'activo' => true,
        ]);

        // 2. Registrar limpieza
        $registroResponse = $this->postJson('/api/vineta-registros', [
            'vineta_id' => $vinetaId,
            'empleado_codigo' => 'EMP-LIMP-1',
            'actividad_id' => $actividadLimpieza->id,
            'cantidad_puros' => 500,
            'fecha_registro' => '2026-09-23',
            'hora_registro' => '09:00',
        ]);

        $registroResponse->assertCreated();
        $codigoVineta = $registroResponse->json('registro.codigo_vineta');
        $this->assertStringStartsWith('l-', $codigoVineta);
        $this->assertNull($registroResponse->json('registro.vineta_api_id'));

        // 3. Verificar en tabla web de viñetas registradas
        $webResponse = $this->actingAs($user)->get(route('vineta-registros.index', ['fecha' => '2026-09-23']));
        $webResponse->assertOk();
        $webResponse->assertSee('ID ' . $codigoVineta);
        $webResponse->assertSee('María Limpiadora');
        $webResponse->assertSee('Limpieza de puros');
    }
}
