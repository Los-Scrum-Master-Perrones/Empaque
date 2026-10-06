<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Empleado;
use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VinetaLimpiezaSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_vista_vinetas_muestra_boton_ver_qr_y_modal(): void
    {
        $user = User::factory()->create();

        $vineta = Vineta::create([
            'api_id' => 23792,
            'item' => '151998',
            'codigo_producto' => 'P-01942',
            'marca' => 'Cuban Rounds',
            'nombre' => 'Robusto',
            'vitola' => 'Robusto 5x50',
            'capa' => 'Maduro',
            'orden' => 'HON-1234',
            'orden_del_sistema' => 'ORD-9876',
            'tipo_empaque' => 'Cajas de 20',
            'cantidad_puros' => 100,
            'estado' => 'Aprobado',
            'impreso' => true,
            'fecha' => '2026-09-17',
        ]);

        $response = $this->actingAs($user)->get(route('vinetas.index'));

        $response->assertOk();
        $response->assertSee('#23792');
        $response->assertSee('btn-ver-qr');
        $response->assertSee('Ver QR');
        $response->assertSee('data-api-id="23792"', false);
        $response->assertSee('vinetaQrModal');
        $response->assertSee('modalQrCanvas');
        $response->assertDontSee('Escanea este código para actividades de limpieza');
    }

    public function test_api_registros_guarda_limpieza_con_secuencia_autoincrementable_l_n(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $vineta = Vineta::create([
            'api_id' => 23792,
            'item' => '151998',
            'codigo_producto' => 'P-01942',
            'marca' => 'Cuban Rounds',
            'nombre' => 'Robusto',
            'vitola' => 'Robusto 5x50',
            'capa' => 'Maduro',
            'orden' => 'HON-1234',
            'orden_del_sistema' => 'ORD-9876',
            'tipo_empaque' => 'Cajas de 20',
            'cantidad_puros' => 100,
            'estado' => 'Aprobado',
            'impreso' => true,
            'fecha' => '2026-09-17',
        ]);

        $empleado = Empleado::create([
            'codigo' => 'EMP-501',
            'nombre' => 'María Pérez',
            'cargo' => 'Limpia Puros',
            'activo' => true,
        ]);

        $actividadLimpieza = Actividad::create([
            'api_id_actividad' => 777,
            'codigo_actividad' => 'ACT-LIMP',
            'nombre' => 'Limpieza de puros',
            'activo' => true,
        ]);

        // 1er registro de limpieza sobre la viñeta 23792
        $store1 = $this->postJson('/api/vineta-registros', [
            'vineta_id' => $vineta->id,
            'empleado_codigo' => 'EMP-501',
            'actividad_id' => $actividadLimpieza->id,
            'cantidad_puros' => 200,
            'fecha_registro' => '2026-09-17',
            'hora_registro' => '08:30',
        ]);

        $store1->assertCreated();
        $store1->assertJsonPath('registro.codigo_vineta', 'l-1');
        $store1->assertJsonPath('registro.vineta_api_id', null);
        $store1->assertJsonPath('registro.vineta_id', $vineta->id);

        $registroDb1 = VinetaRegistro::where('codigo_vineta', 'l-1')->first();
        $this->assertNotNull($registroDb1);
        $this->assertNull($registroDb1->vineta_api_id);
        $this->assertSame($vineta->id, $registroDb1->vineta_id);
        $this->assertSame('151998', $registroDb1->producto_item);
        $this->assertSame('P-01942', $registroDb1->producto_codigo);

        // 2do registro de limpieza sobre la MISMA viñeta en la misma fecha (no debe dar duplicado)
        $store2 = $this->postJson('/api/vineta-registros', [
            'vineta_id' => $vineta->id,
            'empleado_codigo' => 'EMP-501',
            'actividad_id' => $actividadLimpieza->id,
            'cantidad_puros' => 150,
            'fecha_registro' => '2026-09-17',
            'hora_registro' => '10:00',
        ]);

        $store2->assertCreated();
        $store2->assertJsonPath('registro.codigo_vineta', 'l-2');
        $store2->assertJsonPath('registro.vineta_api_id', null);

        // 3er registro de limpieza
        $store3 = $this->postJson('/api/vineta-registros', [
            'vineta_id' => $vineta->id,
            'empleado_codigo' => 'EMP-501',
            'actividad_id' => $actividadLimpieza->id,
            'cantidad_puros' => 180,
            'fecha_registro' => '2026-09-17',
            'hora_registro' => '11:15',
        ]);

        $store3->assertCreated();
        $store3->assertJsonPath('registro.codigo_vineta', 'l-3');

        // Comprobar que en la vista web de Viñetas Registradas aparezcan como "ID l-1", "ID l-2", "ID l-3"
        $webResponse = $this->actingAs($user)->get(route('vineta-registros.index', ['fecha' => '2026-09-17']));
        $webResponse->assertOk();
        $webResponse->assertSee('ID l-1');
        $webResponse->assertSee('ID l-2');
        $webResponse->assertSee('ID l-3');
    }

    public function test_registro_regular_siguiente_no_es_bloqueado_por_limpieza(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $vineta = Vineta::create([
            'api_id' => 23792,
            'item' => '151998',
            'codigo_producto' => 'P-01942',
            'marca' => 'Cuban Rounds',
            'nombre' => 'Robusto',
            'vitola' => 'Robusto 5x50',
            'capa' => 'Maduro',
            'orden' => 'HON-1234',
            'orden_del_sistema' => 'ORD-9876',
            'tipo_empaque' => 'Cajas de 20',
            'cantidad_puros' => 100,
            'estado' => 'Aprobado',
            'impreso' => true,
            'fecha' => '2026-09-17',
        ]);

        $empleadoLimp = Empleado::create([
            'codigo' => 'EMP-501',
            'nombre' => 'María Pérez',
            'cargo' => 'Limpia Puros',
            'activo' => true,
        ]);

        $actividadLimpieza = Actividad::create([
            'api_id_actividad' => 777,
            'codigo_actividad' => 'ACT-LIMP',
            'nombre' => 'Limpieza de puros',
            'activo' => true,
        ]);

        // Registrar limpieza
        $storeLimp = $this->postJson('/api/vineta-registros', [
            'vineta_id' => $vineta->id,
            'empleado_codigo' => 'EMP-501',
            'actividad_id' => $actividadLimpieza->id,
            'cantidad_puros' => 200,
            'fecha_registro' => '2026-09-17',
            'hora_registro' => '08:30',
        ]);
        $storeLimp->assertCreated();
        $storeLimp->assertJsonPath('registro.codigo_vineta', 'l-1');

        // Ahora registrar un proceso regular (Rezagado) sobre la viñeta 23792
        $empleadoRezago = Empleado::create([
            'codigo' => 'EMP-600',
            'nombre' => 'Juan Gómez',
            'cargo' => 'Rezagadora',
            'activo' => true,
        ]);

        $actividadRezago = Actividad::create([
            'api_id_actividad' => 888,
            'codigo_actividad' => 'ACT-REZ',
            'nombre' => 'Rezagado',
            'activo' => true,
        ]);

        $storeRezago = $this->postJson('/api/vineta-registros', [
            'vineta_id' => $vineta->id,
            'empleado_codigo' => 'EMP-600',
            'actividad_id' => $actividadRezago->id,
            'cantidad_puros' => 100,
            'fecha_registro' => '2026-09-17',
            'hora_registro' => '09:00',
        ]);

        $storeRezago->assertCreated();
        $storeRezago->assertJsonPath('registro.vineta_api_id', 23792);
        $this->assertNotSame('l-2', $storeRezago->json('registro.codigo_vineta'));
    }

    public function test_registro_antiguo_de_limpieza_sin_codigo_l_se_actualiza_automaticamente(): void
    {
        $user = User::factory()->create();

        $vineta = Vineta::create([
            'api_id' => 9000,
            'item' => 'SKU8351201',
            'codigo_producto' => 'P-15275',
            'marca' => 'Romeo y Julieta 1875',
            'nombre' => 'Churchill',
            'vitola' => '7X50',
            'capa' => 'Sumatra',
            'orden' => 'PO1334',
            'orden_del_sistema' => '3588',
            'tipo_empaque' => 'MAZOS 1/100',
            'cantidad_puros' => 2000,
            'estado' => 'Aprobado',
            'impreso' => true,
            'fecha' => '2026-09-17',
        ]);

        $empleado = Empleado::create([
            'codigo' => '6658',
            'nombre' => 'ANDRES SANCHEZ GLORIA LETICIA',
            'cargo' => 'A',
            'activo' => true,
        ]);

        // Registro simulado creado con el código anterior (sin l- y con vineta_id 9000)
        $registroAntiguo = VinetaRegistro::create([
            'vineta_id' => $vineta->id,
            'empleado_id' => $empleado->id,
            'empleado_codigo' => '6658',
            'empleado_nombre' => 'ANDRES SANCHEZ GLORIA LETICIA',
            'actividad_codigo' => '103',
            'actividad_nombre' => 'Limpieza de Puros',
            'codigo_vineta' => null,
            'vineta_api_id' => null,
            'cantidad_puros' => 2000,
            'cantidad_cajones' => 1,
            'fecha_registro' => '2026-09-17',
            'hora_registro' => '10:48:00',
            'registrado_en' => '2026-09-17 10:48:00',
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ]);

        // Al acceder a la página web de viñetas registradas, se debe auto-reparar a ID l-1
        $response = $this->actingAs($user)->get(route('vineta-registros.index', ['fecha' => '2026-09-17']));

        $response->assertOk();
        $response->assertSee('ID l-1');
        $response->assertDontSee('ID 9000');

        $this->assertSame('l-1', $registroAntiguo->fresh()->codigo_vineta);
        $this->assertNull($registroAntiguo->fresh()->vineta_api_id);
    }
}
