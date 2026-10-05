<?php

class ModelExtensionModuleLuceedStore extends Model
{
    private const TABLE = 'luceed_store';


    public function isInstalled(): bool
    {
        $table = str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], DB_PREFIX . self::TABLE);
        $query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");

        return (bool) $query->num_rows;
    }


    public function install(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . self::TABLE . "` (
                `store_id` int(11) NOT NULL AUTO_INCREMENT,
                `code` varchar(16) NOT NULL,
                `warehouse_uid` varchar(191) NOT NULL DEFAULT '',
                `name` varchar(255) NOT NULL DEFAULT '',
                `address` varchar(255) NOT NULL DEFAULT '',
                `postal_code` varchar(32) NOT NULL DEFAULT '',
                `city` varchar(128) NOT NULL DEFAULT '',
                `contact` varchar(255) NOT NULL DEFAULT '',
                `pj_uid` varchar(191) NOT NULL DEFAULT '',
                `pj_code` varchar(32) NOT NULL DEFAULT '',
                `pj_name` varchar(255) NOT NULL DEFAULT '',
                `working_hours` text NULL,
                `partner_id` varchar(64) NOT NULL DEFAULT '',
                `sort_order` int(11) NOT NULL DEFAULT 0,
                `status` tinyint(1) NOT NULL DEFAULT 1,
                `is_included` tinyint(1) NOT NULL DEFAULT 0,
                `is_default` tinyint(1) NOT NULL DEFAULT 0,
                `is_availability` tinyint(1) NOT NULL DEFAULT 0,
                `is_store` tinyint(1) NOT NULL DEFAULT 0,
                `is_pickup` tinyint(1) NOT NULL DEFAULT 0,
                `date_added` datetime NOT NULL,
                `date_modified` datetime NOT NULL,
                PRIMARY KEY (`store_id`),
                UNIQUE KEY `uq_luceed_store_code` (`code`),
                UNIQUE KEY `uq_luceed_store_warehouse_uid` (`warehouse_uid`),
                KEY `idx_luceed_store_status_sort` (`status`, `sort_order`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8"
        );
    }


    public function seedLegacy(array $warehouse_config, array $working_hours, array $partner_map, array $rows): int
    {
        if ( ! $this->isInstalled() || $this->getTotalStores() > 0) {
            return 0;
        }

        $flags = [
            'is_included'     => $this->normalizeCodes($warehouse_config['included'] ?? []),
            'is_default'      => $this->normalizeCodes($warehouse_config['default'] ?? []),
            'is_availability' => $this->normalizeCodes($warehouse_config['availability_view'] ?? []),
            'is_store'        => $this->normalizeCodes($warehouse_config['stores'] ?? []),
            'is_pickup'       => $this->normalizeCodes($warehouse_config['pickup'] ?? []),
        ];
        $indexed = [];

        foreach ($rows as $row) {
            if ( ! is_array($row) || empty($row['skladiste'])) {
                continue;
            }

            $indexed[$this->normalizeCode($row['skladiste'])] = $row;
        }

        $codes = array_keys($indexed);
        foreach ($flags as $list) {
            $codes = array_merge($codes, $list);
        }
        $codes = array_values(array_unique(array_filter($codes)));
        sort($codes, SORT_STRING);
        $inserted = 0;

        foreach ($codes as $position => $code) {
            $row = $indexed[$code] ?? [];
            $data = [
                'code'            => $code,
                'warehouse_uid'   => $row['skladiste_uid'] ?? '',
                'name'            => $row['naziv'] ?? ('Poslovnica ' . $code),
                'address'         => $row['adresa'] ?? '',
                'postal_code'     => $row['postanski_broj'] ?? '',
                'city'            => $row['mjesto'] ?? '',
                'contact'         => $row['e_mail'] ?? ($row['telefax'] ?? ''),
                'pj_uid'          => $row['pj_uid'] ?? '',
                'pj_code'         => $row['pj'] ?? '',
                'pj_name'         => $row['pj_naziv'] ?? '',
                'working_hours'   => $working_hours[$code] ?? '',
                'partner_id'      => $partner_map[$code] ?? '',
                'sort_order'      => ($position + 1) * 10,
                'status'          => 1,
                'is_included'     => in_array($code, $flags['is_included'], true) ? 1 : 0,
                'is_default'      => in_array($code, $flags['is_default'], true) ? 1 : 0,
                'is_availability' => in_array($code, $flags['is_availability'], true) ? 1 : 0,
                'is_store'        => in_array($code, $flags['is_store'], true) ? 1 : 0,
                'is_pickup'       => in_array($code, $flags['is_pickup'], true) ? 1 : 0,
            ];

            if ($this->addStore($data)) {
                $inserted++;
            }
        }

        return $inserted;
    }


    public function getStores(): array
    {
        if ( ! $this->isInstalled()) {
            return [];
        }

        return $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . self::TABLE . "` ORDER BY sort_order ASC, code ASC"
        )->rows;
    }


    public function getStore(int $store_id): array
    {
        if ( ! $this->isInstalled()) {
            return [];
        }

        $query = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . self::TABLE . "` WHERE store_id = '" . (int) $store_id . "' LIMIT 1"
        );

        return $query->num_rows ? $query->row : [];
    }


    public function getTotalStores(): int
    {
        if ( ! $this->isInstalled()) {
            return 0;
        }

        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . self::TABLE . "`");

        return (int) $query->row['total'];
    }


    public function addStore(array $data): int
    {
        $data = $this->normalizeData($data);
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . self::TABLE . "` SET " . $this->toSql($data)
            . ", date_added = NOW(), date_modified = NOW()"
        );

        return (int) $this->db->getLastId();
    }


    public function editStore(int $store_id, array $data): void
    {
        $data = $this->normalizeData($data);
        $this->db->query(
            "UPDATE `" . DB_PREFIX . self::TABLE . "` SET " . $this->toSql($data)
            . ", date_modified = NOW() WHERE store_id = '" . (int) $store_id . "'"
        );
    }


    public function validate(array $data, int $store_id = 0): array
    {
        $data = $this->normalizeData($data);
        $errors = [];

        foreach (['code' => 'Šifra', 'warehouse_uid' => 'Luceed UID', 'name' => 'Naziv', 'pj_code' => 'PJ'] as $field => $label) {
            if ($data[$field] === '') {
                $errors[] = $label . ' je obavezno polje.';
            }
        }

        if ($data['code'] !== '' && ! preg_match('/^[A-Za-z0-9_-]+$/', $data['code'])) {
            $errors[] = 'Šifra smije sadržavati samo slova, brojeve, crticu i donju crtu.';
        }

        if ($data['partner_id'] !== '' && ! ctype_digit($data['partner_id'])) {
            $errors[] = 'ID korisnik__partner mora biti numerički.';
        }

        if ($this->isInstalled()) {
            $duplicates = $this->db->query(
                "SELECT code, warehouse_uid, pj_code FROM `" . DB_PREFIX . self::TABLE . "` WHERE store_id != '" . (int) $store_id . "'"
                . " AND (code = '" . $this->db->escape($data['code']) . "'"
                . " OR warehouse_uid = '" . $this->db->escape($data['warehouse_uid']) . "'"
                . " OR (pj_code != '' AND pj_code = '" . $this->db->escape($data['pj_code']) . "'))"
            );

            foreach ($duplicates->rows as $duplicate) {
                if ($duplicate['code'] === $data['code']) {
                    $errors[] = 'Šifra poslovnice već postoji.';
                }
                if ($duplicate['warehouse_uid'] === $data['warehouse_uid']) {
                    $errors[] = 'Luceed UID već postoji.';
                }
                if ($data['pj_code'] !== '' && $duplicate['pj_code'] === $data['pj_code']) {
                    $errors[] = 'PJ oznaka već postoji.';
                }
            }
        }

        return array_values(array_unique($errors));
    }


    public function getWarnings(array $stores): array
    {
        $warnings = [];
        $seen = ['code' => [], 'warehouse_uid' => [], 'pj_code' => []];

        foreach ($stores as $store) {
            $label = ($store['code'] ?: '?') . ' – ' . ($store['name'] ?: 'bez naziva');

            foreach (array_keys($seen) as $field) {
                $value = trim((string) $store[$field]);
                if ($value === '') {
                    continue;
                }
                if (isset($seen[$field][$value])) {
                    $warnings[] = $label . ': duplicirana vrijednost polja ' . $field . ' (' . $value . ').';
                }
                $seen[$field][$value] = true;
            }

            if ($store['code'] === '' || $store['warehouse_uid'] === '' || $store['name'] === '' || $store['pj_code'] === '') {
                $warnings[] = $label . ': nedostaje obavezni podatak (šifra, UID, naziv ili PJ).';
            }
            if ((int) $store['status'] === 1 && ! $this->hasPurpose($store)) {
                $warnings[] = $label . ': aktivna poslovnica nema uključenu nijednu namjenu.';
            }
            if ((int) $store['status'] === 0 && $this->hasPurpose($store)) {
                $warnings[] = $label . ': neaktivna poslovnica još uvijek ima uključene namjene.';
            }
            if ((int) $store['is_pickup'] === 1 && trim((string) $store['working_hours']) === '') {
                $warnings[] = $label . ': pickup poslovnica nema radno vrijeme.';
            }
            if ((int) $store['is_pickup'] === 1 && trim((string) $store['partner_id']) === '') {
                $warnings[] = $label . ': pickup poslovnica nema ID korisnik__partner.';
            }
            if ($store['partner_id'] !== '' && ! ctype_digit((string) $store['partner_id'])) {
                $warnings[] = $label . ': ID korisnik__partner nije numerički.';
            }
            if (((int) $store['is_default'] || (int) $store['is_availability'] || (int) $store['is_store'] || (int) $store['is_pickup']) && ! (int) $store['is_included']) {
                $warnings[] = $label . ': koristi se u užoj grupi, ali nije označena kao Included.';
            }
        }

        return array_values(array_unique($warnings));
    }


    private function normalizeData(array $data): array
    {
        $working_hours = preg_replace('/<br\s*\/?>/i', "\n", (string) ($data['working_hours'] ?? ''));
        $working_hours = trim(strip_tags($working_hours));

        return [
            'code'            => $this->normalizeCode($data['code'] ?? ''),
            'warehouse_uid'   => trim((string) ($data['warehouse_uid'] ?? '')),
            'name'            => trim((string) ($data['name'] ?? '')),
            'address'         => trim((string) ($data['address'] ?? '')),
            'postal_code'     => trim((string) ($data['postal_code'] ?? '')),
            'city'            => trim((string) ($data['city'] ?? '')),
            'contact'         => trim((string) ($data['contact'] ?? '')),
            'pj_uid'          => trim((string) ($data['pj_uid'] ?? '')),
            'pj_code'         => trim((string) ($data['pj_code'] ?? '')),
            'pj_name'         => trim((string) ($data['pj_name'] ?? '')),
            'working_hours'   => $working_hours,
            'partner_id'      => trim((string) ($data['partner_id'] ?? '')),
            'sort_order'      => (int) ($data['sort_order'] ?? 0),
            'status'          => ! empty($data['status']) ? 1 : 0,
            'is_included'     => ! empty($data['is_included']) ? 1 : 0,
            'is_default'      => ! empty($data['is_default']) ? 1 : 0,
            'is_availability' => ! empty($data['is_availability']) ? 1 : 0,
            'is_store'        => ! empty($data['is_store']) ? 1 : 0,
            'is_pickup'       => ! empty($data['is_pickup']) ? 1 : 0,
        ];
    }


    private function toSql(array $data): string
    {
        $parts = [];

        foreach ($data as $key => $value) {
            $parts[] = "`" . $key . "` = '" . $this->db->escape((string) $value) . "'";
        }

        return implode(', ', $parts);
    }


    private function normalizeCodes($codes): array
    {
        if ( ! is_array($codes)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map([$this, 'normalizeCode'], $codes))));
    }


    private function normalizeCode($code): string
    {
        $code = trim((string) $code);

        if ($code !== '' && ctype_digit($code) && strlen($code) < 3) {
            $code = str_pad($code, 3, '0', STR_PAD_LEFT);
        }

        return strtoupper($code);
    }


    private function hasPurpose(array $store): bool
    {
        return (bool) ((int) $store['is_included'] || (int) $store['is_default'] || (int) $store['is_availability'] || (int) $store['is_store'] || (int) $store['is_pickup']);
    }
}
