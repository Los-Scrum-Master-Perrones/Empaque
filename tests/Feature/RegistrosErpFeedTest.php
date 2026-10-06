<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\EmpleadoHoraOrdinaria;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrosErpFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requires_a_valid_date_and_returns_json_without_authentication(): void
    {
        $this->get('/api/registros-erp')
            ->assertUnprocessable()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonValidationErrors('fecha');

        $this->get('/api/registros-erp?fecha=13-08-2026')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fecha');

        $this->get('/api/registros-erp?fecha=2026-08-13&todo=2')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('todo');

        $this->get('/api/registros-erp?fecha=2026-08-13&grupo=grupo_inexistente')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('grupo');
    }

    public function test_it_filters_by_composite_group_like_rezagadoras_rezago_and_parentheses_format(): void
    {
        $empRezago = Empleado::create([
            'codigo' => 'EMP-REZ',
            'nombre' => 'Rezagadora Uno',
            'cargo' => 'Rezago de puros',
            'activo' => true,
        ]);

        $empAnillado = Empleado::create([
            'codigo' => 'EMP-ANI',
            'nombre' => 'Anilladora Uno',
            'cargo' => 'Anillado y celofan',
            'activo' => true,
        ]);

        $empLlenado = Empleado::create([
            'codigo' => 'EMP-LLE',
            'nombre' => 'Llenadora Uno',
            'cargo' => 'Llenado de cajas',
            'activo' => true,
        ]);

        $empLimpieza = Empleado::create([
            'codigo' => 'EMP-LIM',
            'nombre' => 'Limpiadora Uno',
            'cargo' => 'Limpia puros',
            'activo' => true,
        ]);

        $vineta = Vineta::create(['api_id' => 7001, 'impreso' => true]);

        // 1. Rezagadora haciendo Rezago -> rezagadoras_rezago
        $this->createRegistro($vineta, [
            'empleado_id' => $empRezago->id,
            'empleado_codigo' => $empRezago->codigo,
            'empleado_nombre' => $empRezago->nombre,
            'actividad_nombre' => 'Rezagado',
            'fecha_registro' => '2026-08-13',
            'hora_registro' => '08:00:00',
        ]);

        // 2. Rezagadora haciendo Anillado -> rezagadoras_anillado
        $this->createRegistro($vineta, [
            'empleado_id' => $empRezago->id,
            'empleado_codigo' => $empRezago->codigo,
            'empleado_nombre' => $empRezago->nombre,
            'actividad_nombre' => 'Anillado',
            'fecha_registro' => '2026-08-13',
            'hora_registro' => '09:00:00',
        ]);

        // 3. Anilladora haciendo Anillado -> anilladoras_anillado
        $this->createRegistro($vineta, [
            'empleado_id' => $empAnillado->id,
            'empleado_codigo' => $empAnillado->codigo,
            'empleado_nombre' => $empAnillado->nombre,
            'actividad_nombre' => 'Anillado',
            'fecha_registro' => '2026-08-13',
            'hora_registro' => '10:00:00',
        ]);

        // 4. Llenadora haciendo Llenado -> llenadoras_llenado
        $this->createRegistro($vineta, [
            'empleado_id' => $empLlenado->id,
            'empleado_codigo' => $empLlenado->codigo,
            'empleado_nombre' => $empLlenado->nombre,
            'actividad_nombre' => 'Llenado de cajas',
            'fecha_registro' => '2026-08-13',
            'hora_registro' => '11:00:00',
        ]);

        // 5. Limpiadora haciendo Limpieza -> limpiadoras_limpieza
        $this->createRegistro($vineta, [
            'empleado_id' => $empLimpieza->id,
            'empleado_codigo' => $empLimpieza->codigo,
            'empleado_nombre' => $empLimpieza->nombre,
            'actividad_nombre' => 'Limpieza de puros',
            'fecha_registro' => '2026-08-13',
            'hora_registro' => '12:00:00',
        ]);

        // Filtrar por rezagadoras_rezago
        $resRezago = $this->getJson('/api/registros-erp?fecha=2026-08-13&todo=0&grupo=rezagadoras_rezago');
        $resRezago->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('registros.0.empleado_nombre', 'Rezagadora Uno')
            ->assertJsonPath('registros.0.actividad', 'Rezagado')
            ->assertJsonPath('registros.0.grupo', 'rezagadoras_rezago');

        // Filtrar con sintaxis entre paréntesis: rezagadoras(rezago)
        $resRezagoParentesis = $this->getJson('/api/registros-erp?fecha=2026-08-13&todo=0&grupo=rezagadoras(rezago)');
        $resRezagoParentesis->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('registros.0.grupo', 'rezagadoras_rezago');

        // Filtrar rezagadoras(anillado)
        $resRezagoAnillado = $this->getJson('/api/registros-erp?fecha=2026-08-13&todo=0&grupo=rezagadoras(anillado)');
        $resRezagoAnillado->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('registros.0.empleado_nombre', 'Rezagadora Uno')
            ->assertJsonPath('registros.0.actividad', 'Anillado')
            ->assertJsonPath('registros.0.grupo', 'rezagadoras_anillado');

        // Filtrar anilladoras(anillado)
        $resAnilladoras = $this->getJson('/api/registros-erp?fecha=2026-08-13&todo=0&grupo=anilladoras(anillado)');
        $resAnilladoras->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('registros.0.empleado_nombre', 'Anilladora Uno')
            ->assertJsonPath('registros.0.grupo', 'anilladoras_anillado');

        // Filtrar limpiadoras(limpieza)
        $resLimpiadoras = $this->getJson('/api/registros-erp?fecha=2026-08-13&todo=0&grupo=limpiadoras(limpieza)');
        $resLimpiadoras->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('registros.0.empleado_nombre', 'Limpiadora Uno')
            ->assertJsonPath('registros.0.grupo', 'limpiadoras_limpieza');

        // Filtrar llenado general
        $resLlenadoGeneral = $this->getJson('/api/registros-erp?fecha=2026-08-13&todo=0&grupo=llenado');
        $resLlenadoGeneral->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('registros.0.empleado_nombre', 'Llenadora Uno');

        // Filtrar anillado general (debe traer Rezagadora(Anillado) y Anilladora(Anillado))
        $resAnilladoGeneral = $this->getJson('/api/registros-erp?fecha=2026-08-13&todo=0&grupo=anillado');
        $resAnilladoGeneral->assertOk()
            ->assertJsonPath('total', 2);

        // Sin grupo (todos)
        $resTodos = $this->getJson('/api/registros-erp?fecha=2026-08-13&todo=0');
        $resTodos->assertOk()
            ->assertJsonPath('total', 5);
    }

    public function test_it_handles_8219_and_hourly_records(): void
    {
        $emp8219 = Empleado::create([
            'codigo' => '8219',
            'nombre' => 'Empleado Especial 8219',
            'cargo' => 'Sin clasificar',
            'activo' => true,
        ]);

        $vineta = Vineta::create(['api_id' => 7002, 'impreso' => true]);

        // 8219 siempre es rezagadora
        $this->createRegistro($vineta, [
            'empleado_id' => $emp8219->id,
            'empleado_codigo' => '8219',
            'empleado_nombre' => 'Empleado Especial 8219',
            'actividad_nombre' => 'Rezagado',
            'fecha_registro' => '2026-08-13',
            'hora_registro' => '08:00:00',
        ]);

        // Viñeta escaneada por hora de 8219 (rezagadora)
        $vinetaHora = Vineta::create(['api_id' => 7003, 'impreso' => true]);
        $this->createRegistro($vinetaHora, [
            'empleado_id' => $emp8219->id,
            'empleado_codigo' => '8219',
            'empleado_nombre' => 'Empleado Especial 8219',
            'actividad_nombre' => 'Control por hora',
            'raw_payload' => ['modo_registro' => 'por_hora'],
            'minutos_trabajados' => 120,
            'fecha_registro' => '2026-08-13',
            'hora_registro' => '09:00:00',
        ]);

        // Hora ordinaria manual que NO debe salir en el feed de registros ERP
        EmpleadoHoraOrdinaria::create([
            'empleado_id' => $emp8219->id,
            'empleado_codigo' => '8219',
            'empleado_nombre' => 'Empleado Especial 8219',
            'fecha' => '2026-08-13',
            'minutos' => 120,
            'observacion' => 'Apoyo',
        ]);

        // Buscar rezagadoras_hora
        $resHora = $this->getJson('/api/registros-erp?fecha=2026-08-13&todo=0&grupo=rezagadoras_hora');
        $resHora->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('registros.0.empleado_codigo', '8219')
            ->assertJsonPath('registros.0.id_vineta', 7003)
            ->assertJsonPath('registros.0.minutos_por_vineta', 2)
            ->assertJsonPath('registros.0.grupo', 'rezagadoras_hora');

        // Buscar rezagadoras(rezago)
        $resRezago = $this->getJson('/api/registros-erp?fecha=2026-08-13&todo=0&grupo=rezagadoras(rezago)');
        $resRezago->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('registros.0.empleado_codigo', '8219')
            ->assertJsonPath('registros.0.actividad', 'Rezagado')
            ->assertJsonPath('registros.0.grupo', 'rezagadoras_rezago');
    }

    public function test_it_handles_indirectos_group_and_subactivities(): void
    {
        // Empleados indirectos (fuera de rezago, anillado/celofán, llenado/paquetas, limpia puros y 8219/8217)
        $empPrensa = Empleado::create([
            'codigo' => 'EMP-PRENSA',
            'nombre' => 'Control Prensa',
            'cargo' => 'Prensa Puros Control de Calidad',
            'activo' => true,
        ]);

        $empRevisadora = Empleado::create([
            'codigo' => 'EMP-REVISORA',
            'nombre' => 'Revisadora Calidad',
            'cargo' => 'Revisadora de Calidad',
            'activo' => true,
        ]);

        // Empleado directo (rezagadora)
        $empRezago = Empleado::create([
            'codigo' => 'EMP-REZ-DIR',
            'nombre' => 'Rezagadora Directa',
            'cargo' => 'Rezagadora de puros',
            'activo' => true,
        ]);

        $vineta = Vineta::create(['api_id' => 7010, 'impreso' => true]);

        // 1. Indirecto haciendo Rezago -> indirectos_rezago
        $this->createRegistro($vineta, [
            'empleado_id' => $empPrensa->id,
            'empleado_codigo' => $empPrensa->codigo,
            'empleado_nombre' => $empPrensa->nombre,
            'actividad_nombre' => 'Rezagado',
            'fecha_registro' => '2026-08-13',
            'hora_registro' => '08:00:00',
        ]);

        // 2. Indirecto haciendo Anillado -> indirectos_anillado
        $this->createRegistro($vineta, [
            'empleado_id' => $empRevisadora->id,
            'empleado_codigo' => $empRevisadora->codigo,
            'empleado_nombre' => $empRevisadora->nombre,
            'actividad_nombre' => 'Anillado',
            'fecha_registro' => '2026-08-13',
            'hora_registro' => '09:00:00',
        ]);

        // 3. Indirecto con hora ordinaria que NO debe salir en el feed ERP
        EmpleadoHoraOrdinaria::create([
            'empleado_id' => $empRevisadora->id,
            'empleado_codigo' => $empRevisadora->codigo,
            'empleado_nombre' => $empRevisadora->nombre,
            'fecha' => '2026-08-13',
            'minutos' => 60,
            'observacion' => 'Apoyo de calidad',
        ]);

        // 4. Rezagadora directa haciendo Rezago -> rezagadoras_rezago
        $this->createRegistro($vineta, [
            'empleado_id' => $empRezago->id,
            'empleado_codigo' => $empRezago->codigo,
            'empleado_nombre' => $empRezago->nombre,
            'actividad_nombre' => 'Rezagado',
            'fecha_registro' => '2026-08-13',
            'hora_registro' => '10:00:00',
        ]);

        // Filtro grupo=indirectos (debe traer los 2 registros de viñetas de indirectos y excluir a la rezagadora y a las horas ordinarias)
        $resIndirectos = $this->getJson('/api/registros-erp?fecha=2026-08-13&todo=0&grupo=indirectos');
        $resIndirectos->assertOk()
            ->assertJsonPath('total', 2);

        $registrosIndirectos = $resIndirectos->json('registros');
        $codigosIndirectos = collect($registrosIndirectos)->pluck('empleado_codigo')->all();
        $this->assertContains('EMP-PRENSA', $codigosIndirectos);
        $this->assertContains('EMP-REVISORA', $codigosIndirectos);
        $this->assertNotContains('EMP-REZ-DIR', $codigosIndirectos);

        foreach ($registrosIndirectos as $reg) {
            $this->assertEquals('indirectos', $reg['grupo']);
        }

        // Filtro general grupo=rezago (debe incluir rezagadora directa e indirecto haciendo rezago)
        $resRezagoGeneral = $this->getJson('/api/registros-erp?fecha=2026-08-13&todo=0&grupo=rezago');
        $resRezagoGeneral->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_minutos_por_vineta_distribute_sums_exactly_to_target_hours(): void
    {
        $empleado = Empleado::create([
            'codigo' => 'EMP-DIST',
            'nombre' => 'Empleado Distribucion',
            'cargo' => 'Rezago de puros',
            'activo' => true,
        ]);

        $vineta = Vineta::create(['api_id' => 7050, 'impreso' => true]);

        // 28 cajones: 10 con 21 minutos y 18 con 20 minutos = 570 minutos (9.5 horas)
        // Sin algoritmo de distribución: 10 * 0.35 + 18 * 0.33 = 9.44 (error de -0.06)
        for ($i = 0; $i < 28; $i++) {
            $minutos = ($i < 10) ? 21 : 20;
            $this->createRegistro($vineta, [
                'empleado_id' => $empleado->id,
                'empleado_codigo' => $empleado->codigo,
                'empleado_nombre' => $empleado->nombre,
                'actividad_nombre' => 'Rezagado',
                'minutos_trabajados' => $minutos,
                'fecha_registro' => '2026-09-07',
                'hora_registro' => sprintf('%02d:%02d:00', 8 + intdiv($i, 4), ($i % 4) * 15),
            ]);
        }

        // Test API /api/registros-erp
        $resErp = $this->getJson('/api/registros-erp?fecha=2026-09-07&todo=0&grupo=rezagadoras(rezago)');
        $resErp->assertOk()->assertJsonPath('total', 28);

        $registrosErp = $resErp->json('registros');
        $sumErp = array_sum(array_column($registrosErp, 'minutos_por_vineta'));
        $this->assertEquals(9.50, round($sumErp, 2));

        foreach ($registrosErp as $reg) {
            // Cada uno debe tener a lo sumo 2 decimales
            $val = $reg['minutos_por_vineta'];
            $this->assertEquals(round($val, 2), $val);
        }

        // Test API /api/vinetas-registradas
        $resFeed = $this->getJson('/api/vinetas-registradas?fecha=2026-09-07&todo=0&grupo=rezago');
        $resFeed->assertOk()->assertJsonPath('total', 28);

        $registrosFeed = $resFeed->json('registros');
        $sumFeed = array_sum(array_column($registrosFeed, 'minutos_por_vineta'));
        $this->assertEquals(9.50, round($sumFeed, 2));

        foreach ($registrosFeed as $reg) {
            $val = $reg['minutos_por_vineta'];
            $this->assertEquals(round($val, 2), $val);
        }
    }

    private function createRegistro(Vineta $vineta, array $attributes = []): VinetaRegistro
    {
        return VinetaRegistro::create(array_merge([
            'vineta_id' => $vineta->id,
            'codigo_vineta' => 'VIN-'.$vineta->api_id,
            'vineta_api_id' => $vineta->api_id,
            'producto_item' => 'ITEM',
            'producto_codigo' => 'PROD-01',
            'orden_del_sistema' => 'OS-001',
            'orden' => 'OC-001',
            'actividad_nombre' => 'Rezagado',
            'empleado_codigo' => 'EMP-001',
            'empleado_nombre' => 'Empleado Uno',
            'cantidad_puros' => 20,
            'cantidad_cajones' => 1,
            'fecha_registro' => '2026-08-13',
            'hora_registro' => '08:00:00',
            'registrado_en' => '2026-08-13 08:00:00',
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ], $attributes));
    }
}
