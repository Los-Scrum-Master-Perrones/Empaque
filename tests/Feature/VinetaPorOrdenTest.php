<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VinetaPorOrden;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VinetaPorOrdenTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_puede_ver_vista_vinetas_por_orden(): void
    {
        $user = User::factory()->create();

        VinetaPorOrden::create([
            'codigo_qr' => 'o1',
            'orden' => 'ORD-1001',
            'orden_del_sistema' => 'OS-501',
            'item' => 'ITM-99',
            'marca' => 'Flor de Copan',
            'nombre' => 'Corona',
            'vitola' => '5x50',
            'capa' => 'Habano',
            'tipo_empaque' => 'Caja 20',
            'codigo_producto' => 'P-1001',
            'cantidad_puros' => 200,
            'estado' => 'Activo',
            'fecha' => '2026-08-25',
        ]);

        $response = $this->actingAs($user)->get(route('vinetas-por-orden.index'));

        $response->assertOk();
        $response->assertSee('Viñetas por orden');
        $response->assertSee('Flor de Copan');
        $response->assertSee('ORD-1001');
        $response->assertSee('o1');
        $response->assertSee('btn-ver-qr');
        $response->assertSee('data-qr-code="o1"', false);
        $response->assertSee('vinetaQrModal');
    }

    public function test_filtros_de_vinetas_por_orden(): void
    {
        $user = User::factory()->create();

        VinetaPorOrden::create([
            'codigo_qr' => 'o1',
            'marca' => 'Marca A',
            'orden' => 'ORD-A',
            'fecha' => '2026-08-25',
        ]);

        VinetaPorOrden::create([
            'codigo_qr' => 'o2',
            'marca' => 'Marca B',
            'orden' => 'ORD-B',
            'fecha' => '2026-08-25',
        ]);

        $response = $this->actingAs($user)->get(route('vinetas-por-orden.index', [
            'marca' => 'Marca A',
        ]));

        $response->assertOk();
        $response->assertSee('Marca A');
        $response->assertDontSee('Marca B');
    }

    public function test_peticion_ajax_retorna_solo_la_tabla_parcial(): void
    {
        $user = User::factory()->create();

        VinetaPorOrden::create([
            'codigo_qr' => 'o3',
            'marca' => 'Marca C',
            'fecha' => '2026-08-25',
        ]);

        $response = $this->actingAs($user)->get(route('vinetas-por-orden.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertSee('vinetasPorOrdenTableInner');
        $response->assertDontSee('<!DOCTYPE html>');
    }

    public function test_endpoint_siguiente_info_retorna_siguiente_id_y_qr(): void
    {
        $user = User::factory()->create();

        VinetaPorOrden::create([
            'codigo_qr' => 'or-11',
            'api_id' => 11,
            'marca' => 'Marca 11',
            'orden' => '111394',
            'fecha' => '2026-08-25',
        ]);

        $response = $this->actingAs($user)->getJson(route('vinetas-por-orden.siguiente-info'));

        $response->assertOk();
        $response->assertJson([
            'siguiente_id' => 12,
            'siguiente_qr' => 'or-12',
        ]);
    }

    public function test_rechaza_creacion_si_no_se_modifica_ningun_campo(): void
    {
        $user = User::factory()->create();

        $origen = VinetaPorOrden::create([
            'codigo_qr' => 'or-11',
            'api_id' => 11,
            'fecha' => null,
            'item' => '151997',
            'codigo_producto' => 'P-01947',
            'marca' => 'Cuban Rounds',
            'nombre' => 'Toro',
            'vitola' => '6-1/8X50',
            'capa' => 'INDONESIA',
            'orden_del_sistema' => '3606',
            'orden' => '111394',
            'tipo_empaque' => 'Display/24',
            'mes' => 'MAYO 2026',
            'cantidad_puros' => 0,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($user)->postJson(route('vinetas-por-orden.store'), [
            'origen_id' => $origen->id,
            'fecha' => null,
            'item' => '151997',
            'codigo_producto' => 'P-01947',
            'marca' => 'Cuban Rounds',
            'nombre' => 'Toro',
            'vitola' => '6-1/8X50',
            'capa' => 'INDONESIA',
            'orden_del_sistema' => '3606',
            'orden' => '111394',
            'tipo_empaque' => 'Display/24',
            'mes' => 'MAYO 2026',
            'cantidad_puros' => 0,
            'estado' => 'activo',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['general']);
        $response->assertJsonFragment([
            'general' => ['No puedes crear la viñeta con exactamente los mismos datos. Debes modificar al menos un campo.'],
        ]);

        $this->assertDatabaseCount('vinetas_por_orden', 1);
    }

    public function test_permite_crear_si_se_modifica_al_menos_un_campo_con_siguiente_id_y_qr(): void
    {
        $user = User::factory()->create();

        $origen = VinetaPorOrden::create([
            'codigo_qr' => 'or-11',
            'api_id' => 11,
            'fecha' => null,
            'item' => '151997',
            'codigo_producto' => 'P-01947',
            'marca' => 'Cuban Rounds',
            'nombre' => 'Toro',
            'vitola' => '6-1/8X50',
            'capa' => 'INDONESIA',
            'orden_del_sistema' => '3606',
            'orden' => '111394',
            'tipo_empaque' => 'Display/24',
            'mes' => 'MAYO 2026',
            'cantidad_puros' => 0,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($user)->postJson(route('vinetas-por-orden.store'), [
            'origen_id' => $origen->id,
            'fecha' => '2026-09-24',
            'item' => '151997',
            'codigo_producto' => 'P-01947',
            'marca' => 'Cuban Rounds',
            'nombre' => 'Toro',
            'vitola' => '6-1/8X50',
            'capa' => 'INDONESIA',
            'orden_del_sistema' => '3606',
            'orden' => '111395', // campo modificado
            'tipo_empaque' => 'Display/24',
            'mes' => 'SEPTIEMBRE 2026',
            'cantidad_puros' => 100,
            'estado' => 'activo',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseCount('vinetas_por_orden', 2);

        $nueva = VinetaPorOrden::where('codigo_qr', 'or-12')->first();
        $this->assertNotNull($nueva);
        $this->assertEquals(12, $nueva->api_id);
        $this->assertEquals('111395', $nueva->orden);
        $this->assertEquals(100, $nueva->cantidad_puros);

        // Verifica sincronización en tabla vinetas
        $this->assertDatabaseHas('vinetas', [
            'id_pendiente_empaque' => 'or-12',
            'orden' => '111395',
            'orden_del_sistema' => '3606',
        ]);
    }

    public function test_permite_crear_heredando_campos_no_editables_de_origen(): void
    {
        $user = User::factory()->create();

        $origen = VinetaPorOrden::create([
            'codigo_qr' => 'or-11',
            'api_id' => 11,
            'fecha' => '2026-05-15',
            'item' => '151997',
            'codigo_producto' => 'P-01947',
            'marca' => 'Cuban Rounds',
            'nombre' => 'Toro',
            'vitola' => '6-1/8X50',
            'capa' => 'INDONESIA',
            'orden_del_sistema' => '3606',
            'orden' => '111394',
            'tipo_empaque' => 'Display/24',
            'mes' => 'MAYO 2026',
            'cantidad_puros' => 250,
            'estado' => 'activo',
        ]);

        // Se envían únicamente los campos visibles modificados (ej. orden modificada), sin enviar fecha, cantidad_puros ni estado
        $response = $this->actingAs($user)->postJson(route('vinetas-por-orden.store'), [
            'origen_id' => $origen->id,
            'item' => '151997',
            'codigo_producto' => 'P-01947',
            'marca' => 'Cuban Rounds',
            'nombre' => 'Toro',
            'vitola' => '6-1/8X50',
            'capa' => 'INDONESIA',
            'orden_del_sistema' => '3606',
            'orden' => '111399', // modificado
            'tipo_empaque' => 'Display/24',
            'mes' => 'MAYO 2026',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $nueva = VinetaPorOrden::where('codigo_qr', 'or-12')->first();
        $this->assertNotNull($nueva);
        $this->assertEquals('2026-05-15', $nueva->fecha->format('Y-m-d'));
        $this->assertEquals(250, $nueva->cantidad_puros);
        $this->assertEquals('activo', $nueva->estado);
        $this->assertEquals('111399', $nueva->orden);
    }
}

