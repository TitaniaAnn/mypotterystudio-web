<?php
namespace MyPotteryStudio\Tests\Unit;

use MyPotteryStudio\Tests\Support\DatabaseTestCase;
use Database;
use RuntimeException;

class DatabaseTest extends DatabaseTestCase
{
    public function testInsertAndFetchOne(): void
    {
        $id = Database::insert('settings', [
            'setting_key'   => 'site_name',
            'setting_value' => 'Test',
        ]);
        $this->assertGreaterThan(0, $id);

        $row = Database::fetchOne("SELECT * FROM settings WHERE id = ?", [$id]);
        $this->assertSame('site_name', $row['setting_key']);
        $this->assertSame('Test', $row['setting_value']);
    }

    public function testFetchAllReturnsAllRows(): void
    {
        Database::insert('settings', ['setting_key' => 'a', 'setting_value' => '1']);
        Database::insert('settings', ['setting_key' => 'b', 'setting_value' => '2']);

        $rows = Database::fetchAll("SELECT * FROM settings ORDER BY setting_key");
        $this->assertCount(2, $rows);
        $this->assertSame('a', $rows[0]['setting_key']);
    }

    public function testUpdateAffectsRowCount(): void
    {
        $id = Database::insert('settings', ['setting_key' => 'k', 'setting_value' => 'v1']);
        $changed = Database::update('settings', ['setting_value' => 'v2'], 'id = :id', ['id' => $id]);
        $this->assertSame(1, $changed);

        $row = Database::fetchOne("SELECT setting_value FROM settings WHERE id = ?", [$id]);
        $this->assertSame('v2', $row['setting_value']);
    }

    public function testTransactionCommitsOnSuccess(): void
    {
        $id = Database::transaction(function () {
            return Database::insert('settings', ['setting_key' => 'tx_ok', 'setting_value' => 'yes']);
        });
        $row = Database::fetchOne("SELECT setting_value FROM settings WHERE id = ?", [$id]);
        $this->assertSame('yes', $row['setting_value']);
    }

    public function testTransactionRollsBackOnException(): void
    {
        $thrown = false;
        try {
            Database::transaction(function () {
                Database::insert('settings', ['setting_key' => 'tx_bad', 'setting_value' => 'no']);
                throw new RuntimeException('boom');
            });
        } catch (RuntimeException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $row = Database::fetchOne("SELECT * FROM settings WHERE setting_key = ?", ['tx_bad']);
        $this->assertNull($row, 'Insert should have been rolled back');
    }

    public function testFetchOneReturnsNullForMissingRow(): void
    {
        $this->assertNull(Database::fetchOne("SELECT * FROM settings WHERE id = ?", [9999]));
    }
}
