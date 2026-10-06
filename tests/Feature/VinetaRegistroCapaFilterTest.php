<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VinetaRegistroCapaFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_vineta_registros_by_capa_in_single_row_layout(): void
    {
        $user = User::factory()->create();

        $vinetaHabano = Vineta::create([
            'api_id' => 9001,
            'capa' => 'Habano',
            'marca' => 'Plasencia',
            'nombre' => 'Reserva',
        ]);

        $vinetaMaduro = Vineta::create([
            'api_id' => 9002,
            'capa' => 'Maduro',
            'marca' => 'Plasencia',
            'nombre' => 'Alma Fuerte',
        ]);

        VinetaRegistro::create([
            'vineta_id' => $vinetaHabano->id,
            'vineta_api_id' => 9001,
            'codigo_vineta' => 'VR-9001',
            'capa' => 'Habano',
            'marca' => 'Plasencia',
            'actividad_nombre' => 'Anillado',
            'empleado_codigo' => '1001',
            'empleado_nombre' => 'Juan Perez',
            'fecha_registro' => '2026-09-20',
            'hora_registro' => '08:00:00',
            'registrado_en' => '2026-09-20 08:00:00',
            'cantidad_puros' => 100,
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ]);

        VinetaRegistro::create([
            'vineta_id' => $vinetaMaduro->id,
            'vineta_api_id' => 9002,
            'codigo_vineta' => 'VR-9002',
            'capa' => 'Maduro',
            'marca' => 'Plasencia',
            'actividad_nombre' => 'Anillado',
            'empleado_codigo' => '1002',
            'empleado_nombre' => 'Maria Lopez',
            'fecha_registro' => '2026-09-20',
            'hora_registro' => '08:30:00',
            'registrado_en' => '2026-09-20 08:30:00',
            'cantidad_puros' => 150,
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ]);

        $response = $this->actingAs($user)->get(route('vineta-registros.index', ['capa' => 'Habano']));

        $response->assertOk();
        $response->assertSee('Juan Perez');
        $response->assertDontSee('Maria Lopez');
        $response->assertSee('Registros móviles y horas ordinarias.');
        $response->assertSee('name="capa"', false);
    }

    public function test_it_filters_excel_export_by_capa(): void
    {
        $user = User::factory()->create();

        $vinetaHabano = Vineta::create([
            'api_id' => 9003,
            'capa' => 'Habano',
            'marca' => 'Plasencia',
            'nombre' => 'Reserva',
        ]);

        VinetaRegistro::create([
            'vineta_id' => $vinetaHabano->id,
            'vineta_api_id' => 9003,
            'codigo_vineta' => 'VR-9003',
            'capa' => 'Habano',
            'marca' => 'Plasencia',
            'actividad_nombre' => 'Anillado',
            'empleado_codigo' => '1003',
            'empleado_nombre' => 'Carlos Perez',
            'fecha_registro' => '2026-09-20',
            'hora_registro' => '08:00:00',
            'registrado_en' => '2026-09-20 08:00:00',
            'cantidad_puros' => 100,
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ]);

        $exportResponse = $this->actingAs($user)->get(route('vineta-registros.export', ['capa' => 'Habano']));
        $exportResponse->assertOk();
        $this->assertTrue(str_contains($exportResponse->headers->get('content-disposition'), '.xlsx'));
    }
}
