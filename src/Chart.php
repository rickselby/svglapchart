<?php

namespace LapChart;

class Chart
{
    /** @var [] List of drivers */
    protected $drivers;

    /** @var int Size of box (for graph size) */
    protected $boxSize;

    /** @var int Width of driver line */
    protected $pathWidth;

    /** @var int[] List of cars lapped per lap */
    protected $lapped;

    /** @var mixed[] Various settings used when generating the chart */
    protected $settings = [];

    /**
     * Initialise the chart. Optionally set the box size and path width.
     * @param int $boxSize
     * @param int $pathWidth
     */
    public function __construct($boxSize = 20, $pathWidth = 4)
    {
        $this->boxSize = $boxSize;
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

        $height = $this->settings['graphHeight'] + $this->boxSize + ($this->settings['padding'] * 2);

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

                .'<g transform="translate('.($this->settings['namesWidth'] + $this->boxSize).', 0)">'
                    .$this->highlightLapped()
                    .'<g transform="translate('.($this->boxSize / 2).', 0)">'
                        .'<g transform="translate(0, '.($this->boxSize / 2).')">'
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
        $fontSize = $this->boxSize * (5 / 8);

        $this->settings['fontSize'] = $fontSize;
        $this->settings['namesWidth'] = $fontSize * $this->settings['longestName'] * (5 / 8);
        $this->settings['numbersWidth'] = $this->boxSize;
        $this->settings['graphWidth'] = $this->settings['laps'] * $this->boxSize;
        $this->settings['graphHeight'] = count($this->drivers) * $this->boxSize;
        $this->settings['padding'] = $this->boxSize;
    }

    /**
     * Generate driver names and positions
     * @return string
     */
    private function doDriverNamesAndPositions()
    {
        $svg = '';
        foreach($this->drivers AS $driver) {
            $textY = ($this->boxSize * ($driver['positions'][0] - 1))
                + ($this->settings['fontSize']);
            ;
            $svg .= '<text text-anchor="end" '
                . ' x="'.$this->settings['namesWidth'].'" '
                . ' y="' . $textY . '" '
                . ' font-family="Helvetica,sans-serif" '
                . ' font-size="' . $this->settings['fontSize'] . '" '
                . '>' . $driver['name'] . '</text>';

            $svg .= '<text text-anchor="end" '
                . ' x="'.($this->settings['namesWidth'] + $this->boxSize).'" '
                . ' y="' . $textY . '" '
                . ' font-family="Helvetica,sans-serif" '
                . ' font-size="' . $this->settings['fontSize'] . '" '
                . '>' . $driver['positions'][0] . '</text>';

            $svg .= '<text text-anchor="start" '
                . ' x="'.($this->settings['namesWidth'] + $this->boxSize + $this->settings['graphWidth']).'" '
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
                    .' x="'.($lap * $this->boxSize).'" '
                    .' y="'.((count($this->drivers) - $count) * $this->boxSize).'" '
                    .' width="'.$this->boxSize.'" height="'.($this->boxSize * $count).'" '
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
                $points[] = ($lap * $this->boxSize)
                    .','
                    .(($pos-1) * $this->boxSize);
            }

            if (count($points) == 1) {
                $point = explode(',', $points[0]);
                $svg .= '<circle cx="'.$point[0].'" cy="'.$point[1].'" r="'.($this->pathWidth / 2).'" fill="'.$driver['colour'].'" />';
            } else {
                $svg .= '<polyline '
                    .' points="'.implode(' ', $points).'" '
                    .' stroke="'.$driver['colour'].'" '
                    .' stroke-width="'.$this->pathWidth.'" '
                    .' fill="none" '
                    .'></polyline>';
            }

        }

        return $svg;
    }

    /**
     * Generate the 'label' for the laps
     * @return string
     */
    private function doLapLine()
    {

        $svg = '<line '
            .' x1="0" y1="2" '
            .' x2="'.(($this->settings['laps'] - 1) * $this->boxSize).'" y2="2" '
            .' stroke="black" stroke-width="1"></line>';

        for ($i = 0; $i < $this->settings['laps']; $i++) {
            $x = ($i * $this->boxSize);
            $smallFontSize = $this->boxSize * (4 / 8);

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