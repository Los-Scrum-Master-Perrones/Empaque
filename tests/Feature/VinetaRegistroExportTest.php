<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\EmpleadoHoraOrdinaria;
use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class VinetaRegistroExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_product_subtotals_and_employee_totals_in_the_first_sheet(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 3) as $index) {
            $vineta = Vineta::create(['api_id' => 9000 + $index, 'impreso' => true]);
            $this->createRegistro($vineta, [
                'cantidad_puros' => 260,
                'cantidad_actividades' => 4,
                'minutos_trabajados' => 82,
            ]);
        }

        $otraVineta = Vineta::create(['api_id' => 9010, 'impreso' => true]);
        $this->createRegistro($otraVineta, [
            'producto_codigo' => 'P-99999',
            'actividad_nombre' => 'Rezagado',
            'cantidad_puros' => 250,
            'cantidad_actividades' => 2,
            'minutos_trabajados' => 61,
        ]);

        $vinetaOtroEmpleado = Vineta::create(['api_id' => 9020, 'impreso' => true]);
        $this->createRegistro($vinetaOtroEmpleado, [
            'empleado_codigo' => '7000',
            'empleado_nombre' => 'ZUNIGA EMPLEADO DOS',
            'cantidad_puros' => 100,
            'cantidad_actividades' => 1,
            'minutos_trabajados' => 30,
        ]);

        $response = $this->actingAs($user)->get(route('vineta-registros.export', [
            'fecha_desde' => '2026-08-18',
            'fecha_hasta' => '2026-08-18',
        ]));

        $response->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;

        $this->assertTrue($zip->open($path) === true);

        try {
            $workbook = (string) $zip->getFromName('xl/workbook.xml');
            $rows = $this->xlsxRows((string) $zip->getFromName('xl/worksheets/sheet1.xml'));

            $this->assertStringContainsString('name="Resumen agrupado"', $workbook);
            $this->assertStringContainsString('name="Detalle"', $workbook);
            $this->assertStringContainsString('name="Resumen Anillado"', $workbook);
            $this->assertStringContainsString('name="Resumen Rezago"', $workbook);
            $this->assertStringContainsString('name="Resumen Llenado"', $workbook);
            $this->assertStringContainsString('name="Resumen Limpieza"', $workbook);
            $this->assertStringNotContainsString('name="Resumen tiempo diario"', $workbook);

            $anilladoRows = $this->xlsxRows((string) $zip->getFromName('xl/worksheets/sheet3.xml'));
            $rezagoRows = $this->xlsxRows((string) $zip->getFromName('xl/worksheets/sheet4.xml'));
            $limpiezaRows = $this->xlsxRows((string) $zip->getFromName('xl/worksheets/sheet6.xml'));

            $this->assertSame('Empleado', $anilladoRows[0][0] ?? null);
            $this->assertSame('Horas de tarea', $anilladoRows[0][3] ?? null);
            $this->assertSame('Total general', $anilladoRows[count($anilladoRows) - 1][0] ?? null);

            $this->assertStringContainsString('REPORTE DE REZAGO', (string) ($rezagoRows[0][0] ?? ''));
            $this->assertSame('Etiquetas de fila', $rezagoRows[2][0] ?? null);
            $this->assertSame('Suma de Cantidad Procesada', $rezagoRows[2][1] ?? null);
            $this->assertSame('Suma de Horas Trabajadas', $rezagoRows[2][2] ?? null);
            $this->assertSame('Suma de Horas Ordinarias', $rezagoRows[2][3] ?? null);
            $this->assertSame('Total general', $rezagoRows[count($rezagoRows) - 1][0] ?? null);

            $this->assertStringContainsString('REPORTE DE LIMPIEZA', (string) ($limpiezaRows[0][0] ?? ''));
            $this->assertSame('Etiquetas de fila', $limpiezaRows[2][0] ?? null);
            $this->assertSame('Total general', $limpiezaRows[count($limpiezaRows) - 1][0] ?? null);

            $subtotal = collect($rows)->first(fn (array $row) => ($row[3] ?? null) === 'Subtotal producto'
                && ($row[1] ?? null) === 'AGUILAR VALLADARES NALLELY MARIBEL'
                && ($row[5] ?? null) === 'P-03202');

            $this->assertNotNull($subtotal);
            $this->assertSame('3', $subtotal[4]);
            $this->assertSame('VINTAGE 2003', $subtotal[6]);
            $this->assertSame('10104751', $subtotal[7]);
            $this->assertSame('3556', $subtotal[8]);
            $this->assertSame('HON-4295', $subtotal[9]);
            $this->assertSame('2 Anillo, Celofan, Cello (3)', $subtotal[10]);
            $this->assertSame('780', $subtotal[11]);
            $this->assertSame('3120', $subtotal[12]);
            $this->assertSame('246', $subtotal[13]);
            $this->assertSame('4 h 6 min', $subtotal[14]);

            $totalEmpleado = collect($rows)->first(fn (array $row) => ($row[3] ?? null) === 'Total empleado'
                && ($row[1] ?? null) === 'AGUILAR VALLADARES NALLELY MARIBEL');

            $this->assertNotNull($totalEmpleado);
            $this->assertSame('4', $totalEmpleado[4]);
            $this->assertSame('1030', $totalEmpleado[11]);
            $this->assertSame('3620', $totalEmpleado[12]);
            $this->assertSame('307', $totalEmpleado[13]);
            $this->assertSame('5 h 7 min', $totalEmpleado[14]);
            $this->assertSame(2, collect($rows)->where(3, 'Total empleado')->count());
        } finally {
            $zip->close();
            @unlink($path);
        }
    }

    public function test_it_groups_excel_area_sheets_by_puesto_de_trabajo_and_places_8219_in_rezago(): void
    {
        $user = User::factory()->create();

        // 8219 has cargo 'Limpia Brochas' but must be placed in Resumen Rezago
        Empleado::create([
            'codigo' => '8219',
            'nombre' => 'SALGADO SAYRA',
            'cargo' => 'Limpia Brochas',
            'area' => 'Empaque de Brocha Permanente',
            'activo' => true,
        ]);

        // Llenadora has cargo 'Llenado de Cajas y Paquetes' and did an Anillado activity, but must be in Resumen Llenado
        Empleado::create([
            'codigo' => '3001',
            'nombre' => 'LLENADORA MARIA',
            'cargo' => 'Llenado de Cajas y Paquetes',
            'area' => 'Empaque a Tarea Permanente',
            'activo' => true,
        ]);

        // Anilladora has cargo 'Anilladora y Celofanadora'
        Empleado::create([
            'codigo' => '2001',
            'nombre' => 'ANILLADORA JUANA',
            'cargo' => 'Anilladora y Celofanadora',
            'area' => 'Empaque a Tarea Permanente',
            'activo' => true,
        ]);

        // Limpiadora has cargo 'Limpia Puros' and must be in Resumen Limpieza
        Empleado::create([
            'codigo' => '5001',
            'nombre' => 'LIMPIADORA CARMEN',
            'cargo' => 'Limpia Puros',
            'area' => 'Empaque a Tarea Permanente',
            'activo' => true,
        ]);

        $v1 = Vineta::create(['api_id' => 9101, 'impreso' => true]);
        $this->createRegistro($v1, [
            'empleado_codigo' => '8219',
            'empleado_nombre' => 'SALGADO SAYRA',
            'actividad_nombre' => '2 Anillo, Celofan, Cello',
            'cantidad_puros' => 100,
            'cantidad_actividades' => 1,
            'minutos_trabajados' => 60,
        ]);

        $v2 = Vineta::create(['api_id' => 9102, 'impreso' => true]);
        $this->createRegistro($v2, [
            'empleado_codigo' => '3001',
            'empleado_nombre' => 'LLENADORA MARIA',
            'actividad_nombre' => 'Anillado especial',
            'cantidad_puros' => 150,
            'cantidad_actividades' => 1,
            'minutos_trabajados' => 90,
        ]);

        $v3 = Vineta::create(['api_id' => 9103, 'impreso' => true]);
        $this->createRegistro($v3, [
            'empleado_codigo' => '2001',
            'empleado_nombre' => 'ANILLADORA JUANA',
            'actividad_nombre' => 'Anillado',
            'cantidad_puros' => 200,
            'cantidad_actividades' => 1,
            'minutos_trabajados' => 120,
        ]);

        $v4 = Vineta::create(['api_id' => 9104, 'impreso' => true]);
        $this->createRegistro($v4, [
            'empleado_codigo' => '5001',
            'empleado_nombre' => 'LIMPIADORA CARMEN',
            'actividad_nombre' => 'Limpieza',
            'cantidad_puros' => 300,
            'cantidad_actividades' => 1,
            'minutos_trabajados' => 180,
        ]);

        $response = $this->actingAs($user)->get(route('vineta-registros.export', [
            'fecha_desde' => '2026-08-18',
            'fecha_hasta' => '2026-08-18',
        ]));

        $response->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;

        $this->assertTrue($zip->open($path) === true);

        try {
            $anilladoRows = $this->xlsxRows((string) $zip->getFromName('xl/worksheets/sheet3.xml'));
            $rezagoRows = $this->xlsxRows((string) $zip->getFromName('xl/worksheets/sheet4.xml'));
            $llenadoRows = $this->xlsxRows((string) $zip->getFromName('xl/worksheets/sheet5.xml'));
            $limpiezaRows = $this->xlsxRows((string) $zip->getFromName('xl/worksheets/sheet6.xml'));

            // Anilladora in Anillado sheet
            $anilladoText = json_encode($anilladoRows);
            $this->assertStringContainsString('ANILLADORA JUANA', $anilladoText);
            $this->assertStringContainsString('2001', $anilladoText);
            $this->assertSame('Total general', $anilladoRows[count($anilladoRows) - 1][0]);

            // 8219 appears in Rezago sheet
            $rezagoText = json_encode($rezagoRows);
            $this->assertStringContainsString('SALGADO SAYRA', $rezagoText);
            $this->assertStringContainsString('8219', $rezagoText);
            $this->assertStringContainsString('2 Anillo, Celofan, Cello', $rezagoText);
            $this->assertSame('Total general', $rezagoRows[count($rezagoRows) - 1][0]);

            // Llenadora appears in Llenado sheet with her Anillado activity
            $llenadoText = json_encode($llenadoRows);
            $this->assertStringContainsString('LLENADORA MARIA', $llenadoText);
            $this->assertStringContainsString('3001', $llenadoText);
            $this->assertStringContainsString('Anillado especial', $llenadoText);
            $this->assertSame('Total general', $llenadoRows[count($llenadoRows) - 1][0]);

            // Limpiadora appears in Limpieza sheet
            $limpiezaText = json_encode($limpiezaRows);
            $this->assertStringContainsString('LIMPIADORA CARMEN', $limpiezaText);
            $this->assertStringContainsString('5001', $limpiezaText);
            $this->assertStringContainsString('Limpieza', $limpiezaText);
            $this->assertSame('Total general', $limpiezaRows[count($limpiezaRows) - 1][0]);
        } finally {
            $zip->close();
            @unlink($path);
        }
    }

    public function test_it_includes_suma_de_horas_ordinarias_in_resumen_rezago_sheet(): void
    {
        $user = User::factory()->create();

        $empleado = Empleado::create([
            'codigo' => '8219',
            'nombre' => 'SALGADO SAYRA',
            'cargo' => 'Limpia Brochas',
            'area' => 'Empaque de Brocha Permanente',
            'activo' => true,
        ]);

        $v1 = Vineta::create(['api_id' => 9201, 'impreso' => true]);
        $this->createRegistro($v1, [
            'empleado_codigo' => '8219',
            'empleado_nombre' => 'SALGADO SAYRA',
            'actividad_nombre' => 'Rezagado',
            'cantidad_puros' => 120,
            'cantidad_actividades' => 1,
            'minutos_trabajados' => 90,
        ]);

        EmpleadoHoraOrdinaria::create([
            'empleado_id' => $empleado->id,
            'empleado_codigo' => '8219',
            'empleado_nombre' => 'SALGADO SAYRA',
            'fecha' => '2026-08-18',
            'minutos' => 60,
            'observacion' => 'Hora ordinaria rezago',
        ]);

        $response = $this->actingAs($user)->get(route('vineta-registros.export', [
            'fecha_desde' => '2026-08-18',
            'fecha_hasta' => '2026-08-18',
        ]));

        $response->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;

        $this->assertTrue($zip->open($path) === true);

        try {
            $rezagoRows = $this->xlsxRows((string) $zip->getFromName('xl/worksheets/sheet4.xml'));

            $this->assertSame([
                'Etiquetas de fila',
                'Suma de Cantidad Procesada',
                'Suma de Horas Trabajadas',
                'Suma de Horas Ordinarias',
            ], $rezagoRows[2]);

            // Employee row:
            $this->assertSame('SALGADO SAYRA', $rezagoRows[3][0]);
            $this->assertSame('120', $rezagoRows[3][1]);
            $this->assertSame('1.5', $rezagoRows[3][2]);
            $this->assertSame('1', $rezagoRows[3][3]);

            // Employee code row:
            $this->assertSame('   8219', $rezagoRows[4][0]);
            $this->assertSame('120', $rezagoRows[4][1]);
            $this->assertSame('1.5', $rezagoRows[4][2]);
            $this->assertSame('1', $rezagoRows[4][3]);

            // Activity row:
            $this->assertSame('      Rezagado', $rezagoRows[5][0]);
            $this->assertSame('120', $rezagoRows[5][1]);
            $this->assertSame('1.5', $rezagoRows[5][2]);
            $this->assertSame('1', $rezagoRows[5][3]);

            // Total row:
            $this->assertSame('Total general', $rezagoRows[6][0]);
            $this->assertSame('120', $rezagoRows[6][1]);
            $this->assertSame('1.5', $rezagoRows[6][2]);
            $this->assertSame('1', $rezagoRows[6][3]);
        } finally {
            $zip->close();
            @unlink($path);
        }
    }

    public function test_resumen_limpieza_exports_cantidad_procesada_as_estadistico_puros_por_actividades(): void
    {
        $user = User::factory()->create();

        Empleado::create([
            'codigo' => '6658',
            'nombre' => 'ANDRES SANCHEZ GLORIA LETICIA',
            'cargo' => 'Limpia Puros',
            'area' => 'Empaque a Tarea Permanente',
            'activo' => true,
        ]);

        $v1 = Vineta::create(['api_id' => 9201, 'impreso' => true]);
        $this->createRegistro($v1, [
            'empleado_codigo' => '6658',
            'empleado_nombre' => 'ANDRES SANCHEZ GLORIA LETICIA',
            'actividad_nombre' => 'Anillo,Celofan,Cello',
            'cantidad_puros' => 260,
            'cantidad_actividades' => 2,
            'total_actividades' => 520,
            'minutos_trabajados' => 112,
        ]);

        $v2 = Vineta::create(['api_id' => 9202, 'impreso' => true]);
        $this->createRegistro($v2, [
            'empleado_codigo' => '6658',
            'empleado_nombre' => 'ANDRES SANCHEZ GLORIA LETICIA',
            'actividad_nombre' => 'Llenado de Bolsas 3 Puros (Kretek)',
            'cantidad_puros' => 240,
            'cantidad_actividades' => 3,
            'total_actividades' => 720,
            'minutos_trabajados' => 282,
        ]);

        $v3 = Vineta::create(['api_id' => 9203, 'impreso' => true]);
        $this->createRegistro($v3, [
            'empleado_codigo' => '6658',
            'empleado_nombre' => 'ANDRES SANCHEZ GLORIA LETICIA',
            'actividad_nombre' => 'Sellado de bolsas 3 Puros(Kretek)',
            'cantidad_puros' => 1200,
            'cantidad_actividades' => 3,
            'total_actividades' => 3600,
            'minutos_trabajados' => 56,
        ]);

        $response = $this->actingAs($user)->get(route('vineta-registros.export', [
            'fecha_desde' => '2026-08-18',
            'fecha_hasta' => '2026-08-18',
        ]));

        $response->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;

        $this->assertTrue($zip->open($path) === true);

        try {
            $limpiezaRows = $this->xlsxRows((string) $zip->getFromName('xl/worksheets/sheet6.xml'));

            $this->assertStringContainsString('REPORTE DE LIMPIEZA', (string) ($limpiezaRows[0][0] ?? ''));
            $this->assertSame('Etiquetas de fila', $limpiezaRows[2][0]);
            $this->assertSame('Suma de Cantidad Procesada', $limpiezaRows[2][1]);

            // Employee row:
            $this->assertSame('ANDRES SANCHEZ GLORIA LETICIA', $limpiezaRows[3][0]);
            $this->assertSame('4840', $limpiezaRows[3][1]);

            // Code row:
            $this->assertSame('   6658', $limpiezaRows[4][0]);
            $this->assertSame('4840', $limpiezaRows[4][1]);

            // Total row:
            $lastRow = $limpiezaRows[count($limpiezaRows) - 1];
            $this->assertSame('Total general', $lastRow[0]);
            $this->assertSame('4840', $lastRow[1]);
        } finally {
            $zip->close();
            @unlink($path);
        }
    }

    private function createRegistro(Vineta $vineta, array $attributes = []): VinetaRegistro
    {
        return VinetaRegistro::create(array_merge([
            'vineta_id' => $vineta->id,
            'codigo_vineta' => 'VIN-'.$vineta->api_id,
            'vineta_api_id' => $vineta->api_id,
            'producto_codigo' => 'P-03202',
            'producto_item' => '10104751',
            'producto_nombre' => 'Producto prueba',
            'marca' => 'VINTAGE 2003',
            'orden_del_sistema' => '3556',
            'orden' => 'HON-4295',
            'actividad_nombre' => '2 Anillo, Celofan, Cello',
            'empleado_codigo' => '6087',
            'empleado_nombre' => 'AGUILAR VALLADARES NALLELY MARIBEL',
            'cantidad_puros' => 20,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
            'minutos_trabajados' => 10,
            'fecha_registro' => '2026-08-18',
            'hora_registro' => '08:00:00',
            'registrado_en' => '2026-08-18 08:00:00',
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ], $attributes));
    }

    private function xlsxRows(string $xml): array
    {
        preg_match_all('/<row\b[^>]*>(.*?)<\/row>/s', $xml, $rowMatches);

        return collect($rowMatches[1] ?? [])
            ->map(function (string $rowXml) {
                preg_match_all('/<c\b[^>]*>(.*?)<\/c>/s', $rowXml, $cellMatches);

                return collect($cellMatches[1] ?? [])
                    ->map(function (string $cellXml) {
                        if (preg_match('/<t>(.*?)<\/t>/s', $cellXml, $textMatch)) {
                            return html_entity_decode($textMatch[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
                        }

                        return preg_match('/<v>(.*?)<\/v>/s', $cellXml, $valueMatch)
                            ? $valueMatch[1]
                            : '';
                    })
                    ->all();
            })
            ->all();
    }

    public function test_it_exports_empaque_brocha_weekly_sheet_with_31_columns_and_matching_activities_and_prices(): void
    {
        $user = User::factory()->create();

        $empleado = Empleado::create([
            'codigo' => '8217',
            'nombre' => 'FLORES LACUTH JHORMY LYZETH',
            'cargo' => 'Limpia Brochas',
            'area' => 'Empaque de Brocha Permanente',
            'activo' => true,
        ]);

        $vineta = Vineta::create(['api_id' => 9301, 'impreso' => true]);
        $this->createRegistro($vineta, [
            'empleado_codigo' => '8217',
            'empleado_nombre' => 'FLORES LACUTH JHORMY LYZETH',
            'actividad_nombre' => 'Rezago Puros',
            'cantidad_puros' => 4880,
            'cantidad_actividades' => 1,
            'total_actividades' => 4880,
            'fecha_registro' => '2026-07-13',
            'minutos_trabajados' => 480,
        ]);

        EmpleadoHoraOrdinaria::create([
            'empleado_id' => $empleado->id,
            'empleado_codigo' => '8217',
            'empleado_nombre' => 'FLORES LACUTH JHORMY LYZETH',
            'fecha' => '2026-07-15',
            'minutos' => 60,
            'observacion' => 'Hora ordinaria',
        ]);

        $response = $this->actingAs($user)->get(route('vineta-registros.reporte-semanal', [
            'fecha_desde' => '2026-07-13',
            'fecha_hasta' => '2026-07-18',
        ]));

        $response->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;

        $this->assertTrue($zip->open($path) === true);

        try {
            $workbook = (string) $zip->getFromName('xl/workbook.xml');
            $this->assertStringContainsString('name="Empaque brocha"', $workbook);

            preg_match('/<sheet name="Empaque brocha"[^>]*sheetId="(\d+)"/s', $workbook, $match);
            $sheetId = $match[1] ?? '2';
            $brochaRows = $this->xlsxRows((string) $zip->getFromName("xl/worksheets/sheet{$sheetId}.xml"));

            $headerExpected = [
                'Dia',
                'Inc.',
                'Per',
                'S.S.',
                'Lact.',
                'HD',
                'HO',
                '1/PHM',
                '2/PHM',
                '3/PHM',
                '3/PP',
                '4/SHM',
                '5/SHM',
                '6,7/PH',
                'Ani/Cel/Sel',
                'Costu/brocha',
                'Eng/Limpi',
                'Limpi/Gom',
                'Limp/Llenado',
                'Limp/puros',
                'Llen/bol/5',
                'Llen/bol/8',
                'Llen/Disp',
                'Petaca 4',
                'Rez/Bro',
                'Rolado',
                'Rez/pur',
                'Total',
                'Precios',
                'Actividades',
                'Otros Ing.',
            ];

            $headerRow = collect($brochaRows)->first(fn (array $r) => ($r[0] ?? '') === 'Dia');
            $this->assertNotNull($headerRow);
            $this->assertSame($headerExpected, $headerRow);

            $empleadoRow = collect($brochaRows)->first(fn (array $r) => ($r[0] ?? '') === 'COD: 8217');
            $this->assertNotNull($empleadoRow);
            $this->assertSame('FLORES LACUTH JHORMY LYZETH', $empleadoRow[1]);
            $this->assertSame('Puesto:', $empleadoRow[15]);
            $this->assertSame('Limpia Brochas', $empleadoRow[16]);

            $luRow = collect($brochaRows)->first(fn (array $r) => ($r[0] ?? '') === 'LU');
            $this->assertNotNull($luRow);
            $this->assertSame('4880', $luRow[26]);
            $this->assertSame('4880', $luRow[27]);
            $this->assertSame('L 0.00', $luRow[30]);

            $allPrecios = collect($brochaRows)->pluck(28)->filter()->values()->all();
            $this->assertContains('L53.88', $allPrecios);
            $this->assertContains('L431.00', $allPrecios);
            $this->assertContains('L0.0769643', $allPrecios);
            $this->assertContains('L0.0749956', $allPrecios);

            $totalRow = collect($brochaRows)->first(fn (array $r) => ($r[0] ?? '') === 'Total Semanal');
            $this->assertNotNull($totalRow);
            $this->assertSame('1', $totalRow[6]);
            $this->assertSame('4880', $totalRow[26]);
            $this->assertSame('4880', $totalRow[27]);
            $this->assertSame('Totales', $totalRow[29]);
        } finally {
            $zip->close();
            @unlink($path);
        }
    }
}
