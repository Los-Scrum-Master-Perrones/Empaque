<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalizadorRoleSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
    }

    public function test_supervisor_tiene_acceso_solo_a_ver(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');

        $this->actingAs($supervisor);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('catalogos.productos.index'))->assertOk();
        $this->get(route('catalogos.marcas.index'))->assertOk();
        $this->get(route('catalogos.vitolas.index'))->assertOk();
        $this->get(route('catalogos.capas.index'))->assertOk();
        $this->get(route('catalogos.actividades.index'))->assertOk();
        $this->get(route('empleados.index'))->assertOk();
        $this->get(route('vinetas.index'))->assertOk();
        $this->get(route('vinetas-por-orden.index'))->assertOk();
        $this->get(route('vinetas-pendientes.index'))->assertOk();
        $this->get(route('vineta-registros.index'))->assertOk();
        $this->get(route('estadistico.index'))->assertOk();
    }

    public function test_supervisor_no_tiene_acceso_a_usuarios_roles_ni_permisos(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');

        $this->actingAs($supervisor);

        $this->get(route('usuarios.index'))->assertForbidden();
        $this->get(route('usuarios.create'))->assertForbidden();
        $this->get(route('roles.index'))->assertForbidden();
        $this->get(route('permisos.index'))->assertForbidden();
    }

    public function test_supervisor_no_tiene_acceso_a_costos_empaque(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');

        $this->actingAs($supervisor);

        $this->get(route('costos-empaque.index'))->assertForbidden();
    }

    public function test_supervisor_no_puede_sincronizar(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');

        $producto = \App\Models\Producto::create([
            'api_id_producto' => 3001,
            'codigo_producto' => 'PROD-3001',
            'item' => 'ITEM-3001',
            'nombre' => 'Producto prueba',
        ]);
        $actividad = \App\Models\Actividad::create([
            'api_id_actividad' => 101,
            'codigo_actividad' => 'ACT-101',
            'nombre' => 'Anillado',
        ]);

        $this->actingAs($supervisor);

        $this->post(route('catalogos.productos.sincronizar'))->assertForbidden();
        $this->post(route('catalogos.actividades.sincronizar'))->assertForbidden();
        $this->post(route('empleados.sincronizar'))->assertForbidden();
        $this->post(route('vinetas.sincronizar'))->assertForbidden();
        $this->get(route('vinetas.notificaciones'))->assertForbidden();
        $this->post(route('catalogos.productos.actividades.toggle', [$producto, $actividad]))->assertForbidden();
    }

    public function test_supervisor_no_puede_crear_editar_ni_eliminar(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');

        $vineta = \App\Models\Vineta::create([
            'api_id' => 4001,
            'codigo_producto' => 'P-01',
            'item' => 'IT-01',
            'nombre' => 'Test',
            'cantidad_puros' => 10,
        ]);
        $vinetaRegistro = \App\Models\VinetaRegistro::create([
            'vineta_id' => $vineta->id,
            'codigo_vineta' => 'VIN-01',
            'vineta_api_id' => 4001,
            'empleado_codigo' => 'EMP-01',
            'empleado_nombre' => 'Juan',
            'producto_nombre' => 'Producto',
            'actividad_nombre' => 'Anillado',
            'cantidad_puros' => 10,
            'fecha_registro' => '2026-08-12',
            'hora_registro' => '08:00:00',
            'registrado_en' => '2026-08-12 08:00:00',
            'estado' => \App\Models\VinetaRegistro::ESTADO_ACTIVO,
        ]);
        $empleado = \App\Models\Empleado::create([
            'codigo' => 'EMP-01',
            'nombre' => 'Juan',
            'cargo' => 'Anillador',
            'area' => 'Empaque',
            'activo' => true,
        ]);
        $horaOrdinaria = \App\Models\EmpleadoHoraOrdinaria::create([
            'empleado_id' => $empleado->id,
            'empleado_codigo' => 'EMP-01',
            'empleado_nombre' => 'Juan',
            'fecha' => '2026-08-12',
            'minutos' => 60,
            'observacion' => 'Test',
        ]);

        $this->actingAs($supervisor);

        // No puede crear viñeta por orden
        $this->post(route('vinetas-por-orden.store'), [])->assertForbidden();

        // No puede registrar horas ordinarias
        $this->post(route('vineta-registros.horas-ordinarias.store'), [])->assertForbidden();

        // No puede editar horas ordinarias
        $this->patch(route('vineta-registros.horas-ordinarias.update', $horaOrdinaria), [])->assertForbidden();

        // No puede eliminar horas ordinarias
        $this->delete(route('vineta-registros.horas-ordinarias.destroy', $horaOrdinaria))->assertForbidden();

        // No puede editar registro de viñeta
        $this->patch(route('vineta-registros.update', $vinetaRegistro), [])->assertForbidden();

        // No puede eliminar registro de viñeta
        $this->delete(route('vineta-registros.destroy', $vinetaRegistro))->assertForbidden();
    }

    public function test_vistas_renderizan_sin_botones_de_accion_para_supervisor(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');

        $this->actingAs($supervisor);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee(route('costos-empaque.index'));
        $response->assertDontSee(route('usuarios.index'));
        $response->assertDontSee(route('roles.index'));
        $response->assertDontSee(route('permisos.index'));

        // Empleados: no debe salir botón de sincronizar
        $responseEmpleados = $this->get(route('empleados.index'));
        $responseEmpleados->assertOk();
        $responseEmpleados->assertDontSee(route('empleados.sincronizar'));
        $responseEmpleados->assertDontSee('Sincronizar empleados');

        // Viñetas: no debe salir botón de sincronizar
        $responseVinetas = $this->get(route('vinetas.index'));
        $responseVinetas->assertOk();
        $responseVinetas->assertDontSee(route('vinetas.sincronizar'));
        $responseVinetas->assertDontSee('Sincronizar viñetas');

        // Viñetas por orden: no debe salir columna de Acción ni modal de Crear
        $responseVinetasPorOrden = $this->get(route('vinetas-por-orden.index'));
        $responseVinetasPorOrden->assertOk();
        $responseVinetasPorOrden->assertDontSee('id="crearVinetaPorOrdenModal"', false);
    }

    public function test_digitalizador_puede_editar_eliminar_y_sincronizar_empleados_y_vinetas(): void
    {
        $vineta = \App\Models\Vineta::create([
            'api_id' => 5001,
            'codigo_producto' => 'P-50',
            'item' => 'IT-50',
            'nombre' => 'Test',
            'cantidad_puros' => 20,
        ]);
        $vinetaRegistro = \App\Models\VinetaRegistro::create([
            'vineta_id' => $vineta->id,
            'codigo_vineta' => 'VIN-50',
            'vineta_api_id' => 5001,
            'empleado_codigo' => 'EMP-50',
            'empleado_nombre' => 'Pedro',
            'producto_nombre' => 'Producto',
            'actividad_nombre' => 'Anillado',
            'cantidad_puros' => 20,
            'fecha_registro' => now('America/Tegucigalpa')->toDateString(),
            'hora_registro' => '08:00:00',
            'registrado_en' => now('America/Tegucigalpa')->toDateTimeString(),
            'estado' => \App\Models\VinetaRegistro::ESTADO_ACTIVO,
        ]);

        // Digitalizador SÍ ve botones de Editar, Eliminar, Sincronizar empleados y Sincronizar viñetas
        $digitalizador = User::factory()->create(['name' => 'Digitalizador Planta']);
        $digitalizador->assignRole('Digitalizador');

        $resRegistros = $this->actingAs($digitalizador)->get(route('vineta-registros.index'));
        $resRegistros->assertOk();
        $resRegistros->assertSee('title="Eliminar registro"', false);
        $resRegistros->assertSee('title="Editar registro"', false);

        $resEmpleados = $this->actingAs($digitalizador)->get(route('empleados.index'));
        $resEmpleados->assertOk();
        $resEmpleados->assertSee('Sincronizar empleados');

        $resVinetas = $this->actingAs($digitalizador)->get(route('vinetas.index'));
        $resVinetas->assertOk();
        $resVinetas->assertSee('Sincronizar viñetas');

        // Digitalizador puede eliminar un registro en vinetas-registradas
        $this->actingAs($digitalizador)->delete(route('vineta-registros.destroy', $vinetaRegistro))->assertRedirect();
    }
}
