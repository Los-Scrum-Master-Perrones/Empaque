<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VinetaScanUltimaCantidadRegistradaTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_retorna_la_ultima_cantidad_de_puros_registrada_por_item_orden_y_producto(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $empleado = Empleado::create([
            'codigo' => '8217',
            'nombre' => 'FLORES LACUTH JHORMY LYZETH',
            'activo' => true,
        ]);

        // 1. Viñeta inicial registrada en el pasado con 180 puros (a pesar de tener cantidad_puros = 250 por defecto)
        $vinetaA = Vineta::create([
            'api_id' => 26009,
            'id_pendiente_empaque' => '23199',
            'item' => '01608103',
            'codigo_producto' => 'P-23971',
            'orden_del_sistema' => '3601',
            'orden' => 'INT-H-1469',
            'cantidad_puros' => 250,
            'marca' => 'RP Dark Star',
            'nombre' => 'Sixty',
            'estado' => 'activo',
        ]);

        VinetaRegistro::create([
            'vineta_id' => $vinetaA->id,
            'vineta_api_id' => $vinetaA->api_id,
            'empleado_id' => $empleado->id,
            'empleado_codigo' => $empleado->codigo,
            'empleado_nombre' => $empleado->nombre,
            'producto_codigo' => 'P-23971',
            'producto_item' => '01608103',
            'orden_del_sistema' => '3601',
            'orden' => 'INT-H-1469',
            'cantidad_puros' => 180,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 180,
            'actividad_nombre' => 'Rezagado',
            'fecha_registro' => now()->toDateString(),
            'hora_registro' => now()->toTimeString(),
            'registrado_en' => now(),
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ]);

        // 2. Nueva viñeta del mismo item, orden del sistema, orden y código de producto (con valor por defecto 250 en la tabla)
        $vinetaB = Vineta::create([
            'api_id' => 26010,
            'id_pendiente_empaque' => '23199-B',
            'item' => '01608103',
            'codigo_producto' => 'P-23971',
            'orden_del_sistema' => '3601',
            'orden' => 'INT-H-1469',
            'cantidad_puros' => 250,
            'marca' => 'RP Dark Star',
            'nombre' => 'Sixty',
            'estado' => 'activo',
        ]);

        // Al escanear vinetaB, el endpoint debe devolver 180 (la cantidad del último registro guardado)
        $response = $this->postJson('/api/vinetas/scan', ['qr' => '26010']);

        $response->assertOk();
        $this->assertEquals(180, $response->json('vineta.cantidad_puros'));

        // 3. Si se guarda un nuevo registro sobre vinetaB con 195 puros
        VinetaRegistro::create([
            'vineta_id' => $vinetaB->id,
            'vineta_api_id' => $vinetaB->api_id,
            'empleado_id' => $empleado->id,
            'empleado_codigo' => $empleado->codigo,
            'empleado_nombre' => $empleado->nombre,
            'producto_codigo' => 'P-23971',
            'producto_item' => '01608103',
            'orden_del_sistema' => '3601',
            'orden' => 'INT-H-1469',
            'cantidad_puros' => 195,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 195,
            'actividad_nombre' => 'Rezagado',
            'fecha_registro' => now()->toDateString(),
            'hora_registro' => now()->toTimeString(),
            'registrado_en' => now(),
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ]);

        // Ahora al escanear otra viñeta del mismo producto/orden, debe devolver 195
        $vinetaC = Vineta::create([
            'api_id' => 26011,
            'id_pendiente_empaque' => '23199-C',
            'item' => '01608103',
            'codigo_producto' => 'P-23971',
            'orden_del_sistema' => '3601',
            'orden' => 'INT-H-1469',
            'cantidad_puros' => 250,
            'marca' => 'RP Dark Star',
            'nombre' => 'Sixty',
            'estado' => 'activo',
        ]);

        $responseC = $this->postJson('/api/vinetas/scan', ['qr' => '26011']);
        $responseC->assertOk();
        $this->assertEquals(195, $responseC->json('vineta.cantidad_puros'));

        // 4. Viñeta de producto diferente sin registros previos debe mantener su default (250)
        $vinetaDiferente = Vineta::create([
            'api_id' => 26012,
            'id_pendiente_empaque' => '99999',
            'item' => '99999999',
            'codigo_producto' => 'P-99999',
            'orden_del_sistema' => '9999',
            'orden' => 'ORD-9999',
            'cantidad_puros' => 250,
            'marca' => 'Marca Diferente',
            'nombre' => 'Robusto',
            'estado' => 'activo',
        ]);

        $responseDif = $this->postJson('/api/vinetas/scan', ['qr' => '26012']);
        $responseDif->assertOk();
        $this->assertEquals(250, $responseDif->json('vineta.cantidad_puros'));
    }
}
