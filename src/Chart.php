<?php

namespace LapChart;

class Chart
{
    /** @var [] List of drivers */
    protected $drivers;

    /** @var int Size of box (for graph size) */
    protected $lineHeight;

    /** @var int Width of driver line */
    protected $pathWidth;

    /** @var int[] List of cars lapped per lap */
    protected $lapped = [];

    /** @var mixed[] Various settings used when generating the chart */
    protected $settings = [];

    /**
     * Initialise the chart. Optionally set the box size and path width.
     * @param int $lineHeight
     * @param int $pathWidth
     */
    public function __construct($lineHeight = 20, $pathWidth = 4)
    {
        $this->lineHeight = $lineHeight;
        $this->pathWidth = $pathWidth;
        $this->settings = [
            'laps' => 0,
            'longestName' => 0,
        ];
    }

    /**
     * Add a driver and their positions
     * @param $name
     * @param $colour
     * @param $positions
     */
    public function setDriver($name, $colour, $positions)
    {
        $this->drivers[] = [
            'name' => $name,
            'colour' => $colour,
            'positions' => $positions,
        ];

        $this->settings['laps'] = max($this->settings['laps'], count($positions));
        $this->settings['longestName'] = max($this->settings['longestName'], strlen($name));
    }

    /**
     * Set the number of drivers lapped for each lap
     * @param int[] $lapped
     */
    public function setLapped($lapped)
    {
        $this->lapped = $lapped;
    }

    /**
     * Generate the SVG
     * @return string
     */
    public function generate()
    {
        $this->setSettings();

        /**
         * Get Width and Height of the image
         */
        $width = $this->settings['graphWidth'] + $this->settings['namesWidth']
            + ($this->settings['numbersWidth'] * 2) + ($this->settings['padding'] * 2);

        $height = $this->settings['graphHeight'] + $this->lineHeight + ($this->settings['padding'] * 2);

        // Header
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'">'
            // Border
            .'<rect x="0" y="0" '
                .' width="'.$width.'" height="'.$height.'" '
                .' style="stroke-width:1 ; stroke: black ; fill: none;" '
            .'></rect>'
            // Add padding
            .'<g transform="translate('.$this->settings['padding'].', '.$this->settings['padding'].')">'

                .'<g>'.$this->doDriverNamesAndPositions().'</g>'

                .'<g transform="translate('.($this->settings['namesWidth'] + $this->lineHeight).', 0)">'
                    .$this->highlightLapped()
                    .'<g transform="translate('.($this->settings['graphBoxWidth'] / 2).', 0)">'
                        .'<g transform="translate(0, '.($this->lineHeight / 2).')">'
                            .$this->doDriverLines()
                        .'</g>'

                        .'<g transform="translate(0, '.$this->settings['graphHeight'].')">'
                            .$this->doLapLine()
                        .'</g>'
                    .'</g>'
                .'</g>'
            .'</g>'
        .'</svg>';

        return $svg;
    }

    /**
     * Set up the settings for the graph
     */
    private function setSettings()
    {
        $fontSize = $this->lineHeight * (5 / 8);

        $this->settings['fontSize'] = $fontSize;
        $this->settings['namesWidth'] = $fontSize * $this->settings['longestName'] * (5 / 8);
        $this->settings['numbersWidth'] = $this->lineHeight;
        $this->settings['graphHeight'] = count($this->drivers) * $this->lineHeight;
        // Golden Ratio!
        $this->settings['graphWidth'] = 1.61803398875 * $this->settings['graphHeight'];
        $this->settings['graphBoxWidth'] = $this->settings['graphWidth'] / $this->settings['laps'];
        $this->settings['padding'] = $this->lineHeight / 2;
    }

    /**
     * Generate driver names and positions
     * @return string
     */
    private function doDriverNamesAndPositions()
    {
        $svg = '';
        foreach($this->drivers AS $driver) {
            $textY = ($this->lineHeight * ($driver['positions'][0] - 1))
                + ($this->settings['fontSize']);
            ;
            $svg .= '<text text-anchor="end" '
                . ' x="'.$this->settings['namesWidth'].'" '
                . ' y="' . $textY . '" '
                . ' font-family="Helvetica,sans-serif" '
                . ' font-size="' . $this->settings['fontSize'] . '" '
                . '>' . $driver['name'] . '</text>';

            $svg .= '<text text-anchor="end" '
                . ' x="'.($this->settings['namesWidth'] + $this->lineHeight).'" '
                . ' y="' . $textY . '" '
                . ' font-family="Helvetica,sans-serif" '
                . ' font-size="' . $this->settings['fontSize'] . '" '
                . '>' . $driver['positions'][0] . '</text>';

            $svg .= '<text text-anchor="start" '
                . ' x="'.($this->settings['namesWidth'] + $this->lineHeight + $this->settings['graphWidth']).'" '
                . ' y="' . $textY . '" '
                . ' font-family="Helvetica,sans-serif" '
                . ' font-size="' . $this->settings['fontSize'] . '" '
                . '>' . $driver['positions'][0] . '</text>';

        }
        return $svg;
    }

    /**
     * Generate gray background for lapped drivers
     * @return string
     */
    private function highlightLapped()
    {
        $svg = '';
        foreach($this->lapped AS $lap => $count) {
            if ($count > 0) {
                $svg .= '<rect '
                    .' x="'.($lap * $this->settings['graphBoxWidth']).'" '
                    .' y="'.((count($this->drivers) - $count) * $this->lineHeight).'" '
                    .' width="'.$this->settings['graphBoxWidth'].'" height="'.($this->lineHeight * $count).'" '
                    .' style="stroke-width:0; fill: lightgray" '
                    .'></rect>';

            }
        }
        return $svg;
    }

    /**
     * Generate lines to show each drivers' progress
     * @return string
     */
    private function doDriverLines()
    {
        $svg = '';
        foreach($this->drivers AS $driver) {

            $points = [];
            foreach($driver['positions'] AS $lap => $pos) {
                $points[] = ($lap * $this->settings['graphBoxWidth'])
                    .','
                    .(($pos-1) * $this->lineHeight);
            }

            if (count($points) == 1) {
                $point = explode(',', $points[0]);
                $svg .= '<circle cx="'.$point[0].'" cy="'.$point[1].'" r="'.($this->pathWidth / 2).'" ';
                if ($this->getSecondaryColour($driver)) {
                    $svg .= ' stroke="'.$this->getPrimaryColour($driver).'" '
                        .' fill="'.$this->getSecondaryColour($driver).'" ';
                } else {
                    $svg .= ' fill="'.$this->getPrimaryColour($driver).'" ';
                }
                $svg .= ' />';
            } else {
                $svg .= '<polyline '
                    . ' points="' . implode(' ', $points) . '" '
                    . ' stroke="' . $this->getPrimaryColour($driver) . '" '
                    . ' stroke-width="' . $this->pathWidth . '" '
                    . ' stroke-linecap="round" '
                    . ' fill="none" '
                    . '></polyline>';
                if ($this->getSecondaryColour($driver)) {
                    $svg .= '<polyline '
                        . ' points="' . implode(' ', $points) . '" '
                        . ' stroke="' . $this->getSecondaryColour($driver) . '" '
                        . ' stroke-width="'.($this->pathWidth * (1/3)).'" '
                        . ' stroke-linecap="round" '
                        . ' fill="none" '
                        . '></polyline>';
                }
            }
        }

        return $svg;
    }

    /**
     * Get the drivers' primary colour
     * @param $driver
     * @return string
     */
    private function getPrimaryColour($driver)
    {
        if (is_array($driver['colour'])) {
            return $driver['colour'][0];
        } else {
            return $driver['colour'];
        }
    }

    /**
     * Get the drivers' secondary colour (if it exists)
     * @param $driver
     * @return string|null
     */
    private function getSecondaryColour($driver)
    {
        if (is_array($driver['colour'])) {
            return $driver['colour'][1];
        } else {
            return null;
        }
    }

    /**
     * Generate the 'label' for the laps
     * @return string
     */
    private function doLapLine()
    {
        $svg = '<line '
            .' x1="0" y1="2" '
            .' x2="'.(($this->settings['laps'] - 1) * $this->settings['graphBoxWidth']).'" y2="2" '
            .' stroke="black" stroke-width="1"></line>';

        for ($i = 0; $i < $this->settings['laps']; $i++) {
            $x = ($i * $this->settings['graphBoxWidth']);
            $smallFontSize = $this->lineHeight * (4 / 8);

            $svg .= '<line '
                .' x1="'.$x.'" y1="0" '
                .' x2="'.$x.'" y2="4" '
                .' stroke="black" stroke-width="1"></line>';

            $svg .= '<text text-anchor="middle" '
                . ' x="'.$x.'" '
                . ' y="'.(4 + $smallFontSize).'" '
                . ' font-family="Helvetica,sans-serif" '
                . ' font-size="' . $smallFontSize . '" '
                . '>' . ($i == 0 ? 'Start' : $i) . '</text>';
        }

        return $svg;
    }
}
