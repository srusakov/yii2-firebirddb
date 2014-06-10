<?php
/*
 * @author <srusakov@gmail.com>
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace srusakov\firebirddb;

/**
 * Description of FirebirdMigration
 *
 * @author rusakov
 */
class FirebirdMigration extends \yii\db\Migration
{

  /**
   * @inheritdoc
   */
  public function createTable($name, $columns, $options=null)
  {
    $baseTableName = $this->db->getSchema()->getRawTableName($name);
    if ($this->db->getSchema()->getTableSchema($baseTableName, true) !== null) {
      echo "    > table $name already exists ...\n";
      return;
    }
    parent::createTable($name, $columns, $options);
  }

  

  /**
   * Create trigger for auto-sequence primary key field
   * @param string $baseName name of trigger, table and sequence (all the same)
   * @param string $pk name of primary key field (usually 'ID')
   */
  public function createPKTrigger($triggerName, $tableName=null, $pkFieldName='id', $seqName=null)
  {
    echo "    > create trigger $triggerName ...";
    $time = microtime(true);

    if (empty($tableName)) {
      $tableName = $triggerName;
    }
    if (empty($seqName)) {
      $seqName = $tableName;
    }
    $rawTableName = $this->db->getSchema()->getRawTableName($tableName);
    $this->db->createCommand("create trigger {$triggerName} for {$rawTableName}
      active before insert position 0 as
            begin
            new.{$pkFieldName} = gen_id({$seqName}, 1);
            end
            ")->execute();
    echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
  }


  /**
   * Create sequence for PK autoincrement fields
   * @param string $name SEQ name
   * @param integer $value Initial value
   */
  public function createSequence($name, $value=null)
  {
    echo "    > create sequence $name ...";
    $time = microtime(true);

    $rawName = $this->db->getSchema()->getRawTableName($name);
    $this->db->createCommand("CREATE SEQUENCE {$rawName}")->execute();
    if ($value > 0) {
      $this->db->createCommand("ALTER SEQUENCE {$rawName} RESTART WITH {$value}")->execute();
    }

    echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
  }

  public function dropSequence($name)
  {
    echo "    > drop sequence $name ...";
    $time = microtime(true);

    $rawName = $this->db->getSchema()->getRawTableName($name);
    $this->db->createCommand("DROP SEQUENCE {$rawName}")->execute();

    echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
  }
}
