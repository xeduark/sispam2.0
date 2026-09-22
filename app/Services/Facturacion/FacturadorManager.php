<?php

namespace App\Services\Facturacion;

use App\Services\Facturacion\Adapters\AlegraAdapter;
use App\Services\Facturacion\Adapters\CustomRestAdapter;
use App\Services\Facturacion\Adapters\FacturaTechAdapter;
use App\Services\Facturacion\Adapters\SiigoAdapter;
use App\Services\Facturacion\Adapters\SimuladorAdapter;
use App\Services\Facturacion\Adapters\TheFactoryHkaAdapter;
use Illuminate\Support\Facades\DB;
use PDO;

class FacturadorManager {

    private PDO $db;
    private array $adapters = [];

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? DB::connection()->getPdo();
        $this->registerAdapter(new SimuladorAdapter());
        $this->registerAdapter(new FacturaTechAdapter());
        $this->registerAdapter(new SiigoAdapter());
        $this->registerAdapter(new AlegraAdapter());
        $this->registerAdapter(new TheFactoryHkaAdapter());
        $this->registerAdapter(new CustomRestAdapter());
    }

    public function registerAdapter(FacturadorAdapterInterface $adapter): void {
        $this->adapters[$adapter->getCodigo()] = $adapter;
    }

    public function getAvailableAdapters(): array {
        $list = [];
        foreach ($this->adapters as $codigo => $adapter) {
            $list[$codigo] = $adapter->getNombre();
        }
        return $list;
    }

    public function getActiveConfig(): ?array {
        $stmt = $this->db->query("SELECT * FROM facturas_config_proveedor WHERE activo = 1 ORDER BY id DESC LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        return $config ?: null;
    }

    public function getActiveAdapter(): FacturadorAdapterInterface {
        $config = $this->getActiveConfig();
        $codigo = $config['proveedor_codigo'] ?? 'SIMULADOR';
        return $this->adapters[$codigo] ?? $this->adapters['SIMULADOR'];
    }

    /**
     * Emite una factura electrónica a través del proveedor activo
     */
    public function emitir(array $datosFactura): FacturadorResponse {
        $config = $this->getActiveConfig();
        if (!$config) {
            return new FacturadorResponse(
                success: false,
                message: 'No existe una configuración de proveedor tecnológico activa.'
            );
        }

        $adapter = $this->getActiveAdapter();
        return $adapter->emitirFactura($datosFactura, $config);
    }

    /**
     * Genera la estructura RIPS JSON Res. 2275 a través del adaptador
     */
    public function generarRips(array $datosFactura): array {
        $adapter = $this->getActiveAdapter();
        return $adapter->generarRipsJson($datosFactura);
    }
}
