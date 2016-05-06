Generate SVG lap charts in PHP.

## Example

```php
// Create the chart
$chart = new chart();

// Set information for each driver
foreach($drivers AS $driver) {
    $chart->setDriver($driver['name'], $driver['colour'], $driver['positions']);
}

// Set how many drivers were lapped on each lap
$chart->setLapped($lapped);

// Get the SVG
echo $chart->generate();
```
