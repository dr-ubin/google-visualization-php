<?php
  namespace Google\Visualization\DataSource\Query;

  use Google\Visualization\DataSource\Query\ScalarFunction\ScalarFunction;

  class ScalarFunctionColumn extends AbstractColumn
  {
    const COLUMN_FUNCTION_TYPE_SEPARATOR = "_";
    const COLUMN_COLUMN_SEPARATOR = ",";

    protected $columns;
    protected $scalarFunction;

    public function __construct($columns, ScalarFunction $scalarFunction)
    {
      $this->columns = $columns;
      $this->scalarFunction = $scalarFunction;
    }

    public function getId()
    {
      $colIds = array();
      foreach ($this->columns as $col)
      {
        $colIds[] = $col->getId();
      }
      return $this->scalarFunction->getFunctionName() . self::COLUMN_FUNCTION_TYPE_SEPARATOR . implode(self::COLUMN_COLUMN_SEPARATOR, $colIds);
    }

    public function getFunction()
    {
      return $this->scalarFunction;
    }

    public function getColumns()
    {
      return $this->columns;
    }

    public function getAllSimpleColumns()
    {
      $simpleColumns = array();
      foreach ($this->columns as $col)
      {
        $simpleColumns = array_merge($simpleColumns, $col->getAllSimpleColumns());
      }
      return $simpleColumns;
    }

    public function getAllAggregationColumns()
    {
      $aggregationColumns = array();
      foreach ($this->columns as $col)
      {
        $aggregationColumns = array_merge($aggregationColumns, $col->getAllAggregationColumns());
      }
      return $aggregationColumns;
    }

    public function getAllScalarFunctionColumns()
    {
      $scalarFunctionColumns = array();
      foreach ($this->columns as $col)
      {
        $scalarFunctionColumns = array_merge($scalarFunctionColumns, $col->getAllScalarFunctionColumns());
      }
      return $scalarFunctionColumns;
    }

    public function validateColumn(DataTable $dataTable)
    {
      $types = array();
      foreach ($this->columns as $column)
      {
        $column->validateColumn($dataTable);
        $types[] = $column->getValueType($dataTable);
      }
      $this->scalarFunction->validateParameters($types);
    }

    public function getValueType(DataTable $dataTable)
    {
      if ($dataTable->containsColumn($this->getId()))
      {
        return $dataTable->getColumnDescription($this->getId())->getType();
      }
      $types = array();
      foreach ($this->columns as $columns)
      {
        $types[] = $column->getValueType($dataTable);
      }
      return $this->scalarFunction->getReturnType($types);
    }
  }
?>
