<?php
namespace astuteo\pjeShared\migrations;


use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

class Install extends Migration
{
    public string $driver;
    public function safeUp() : bool
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->addForeignKeys();
            Craft::$app->db->schema->refresh();
        }
        return true;
    }

    public function safeDown() : bool
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();
        return true;
    }

    protected function createTables() : bool
    {
        $tablesCreated = false;
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%pjeshared_breadcrumbs}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%pjeshared_breadcrumbs}}',
                [
                    'id' => $this->primaryKey(),
                    'entryId' => $this->integer()->notNull(),
                    'navigationId' => $this->integer(),
                    'startEntryId' => $this->integer(),
                    'navigationHandle' => $this->string(255),
                    'startEntryLabel' => $this->string(255),
                    'fieldOwnerId' => $this->integer()->notNull(),
                    'fieldOwnerHandle' => $this->string(255),
                ]
            );
        }
        return $tablesCreated;
    }

    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%pjeshared_breadcrumbs}}', 'entryId'),
            '{{%pjeshared_breadcrumbs}}',
            'entryId',
            '{{%content}}',
            'elementId',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%pjeshared_breadcrumbs}}', 'startEntryId'),
            '{{%pjeshared_breadcrumbs}}',
            'startEntryId',
            '{{%content}}',
            'elementId',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%pjeshared_breadcrumbs}}', 'navigationId'),
            '{{%pjeshared_breadcrumbs}}',
            'navigationId',
            '{{%pjeshared_navigation}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }


    protected function removeTables()
    {
        $this->dropTableIfExists('{{%pjeshared_breadcrumbs}}');
        $this->dropTableIfExists('{{%pjeshared_navigation}}');
    }
}
