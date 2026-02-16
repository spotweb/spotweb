<?php

namespace PhpCoveralls\Bundle\CoverallsBundle\Collector;

use PhpCoveralls\Bundle\CoverallsBundle\Entity\JsonFile;
use PhpCoveralls\Bundle\CoverallsBundle\Entity\SourceFile;

/**
 * Coverage collector for clover.xml.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class CloverXmlCoverageCollector
{
    /**
     * JsonFile.
     *
     * @var JsonFile
     */
    protected $jsonFile;

    // API

    /**
     * Collect coverage from XML object.
     *
     * @param \SimpleXMLElement $xml     clover XML object
     * @param string            $rootDir path to repository root directory
     *
     * @return JsonFile
     */
    public function collect(\SimpleXMLElement $xml, $rootDir)
    {
        $root = rtrim($rootDir, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR;

        if (null === $this->jsonFile) {
            $this->jsonFile = new JsonFile();
        }

        // overwrite if run_at has already been set
        $runAt = $this->collectRunAt($xml);
        $this->jsonFile->setRunAt($runAt);

        $xpaths = [
            '/coverage/project/file',
            '/coverage/project/package/file',
        ];

        foreach ($xpaths as $xpath) {
            foreach ($xml->xpath($xpath) as $file) {
                $srcFile = $this->collectFileCoverage($file, $root);

                if (null !== $srcFile) {
                    $this->jsonFile->addSourceFile($srcFile);
                }
            }
        }

        return $this->jsonFile;
    }

    // accessor

    /**
     * Return json file.
     *
     * @return JsonFile
     */
    public function getJsonFile()
    {
        return $this->jsonFile;
    }

    // Internal method

    /**
     * Collect timestamp when the job ran.
     *
     * @param \SimpleXMLElement $xml    clover XML object of a file
     * @param string            $format dateTime format
     *
     * @return string
     */
    protected function collectRunAt(\SimpleXMLElement $xml, $format = 'Y-m-d H:i:s O')
    {
        $timestamp = $xml->project['timestamp'];
        $runAt = new \DateTime('@'.$timestamp);

        return $runAt->format($format);
    }

    /**
     * Collect coverage data of a file.
     *
     * @param \SimpleXMLElement $file clover XML object of a file
     * @param string            $root path to src directory
     *
     * @return null|SourceFile
     */
    protected function collectFileCoverage(\SimpleXMLElement $file, $root)
    {
        $absolutePath = realpath((string) ($file['path'] ?: $file['name']));

        if (false === strpos($absolutePath, $root)) {
            return;
        }

        $filename = $absolutePath;

        if (\DIRECTORY_SEPARATOR !== $root) {
            $filename = str_replace($root, '', $absolutePath);
        }

        return $this->collectCoverage($file, $absolutePath, $filename);
    }

    /**
     * Collect coverage data.
     *
     * @param \SimpleXMLElement $file     clover XML object of a file
     * @param string            $path     path to source file
     * @param string            $filename filename
     *
     * @return SourceFile
     */
    protected function collectCoverage(\SimpleXMLElement $file, $path, $filename)
    {
        if ($this->jsonFile->hasSourceFile($path)) {
            $srcFile = $this->jsonFile->getSourceFile($path);
        } else {
            $srcFile = new SourceFile($path, $filename);
        }

        foreach ($file->line as $line) {
            if ('stmt' === (string) $line['type']) {
                $lineNum = (int) $line['num'];

                if ($lineNum > 0) {
                    $srcFile->addCoverage($lineNum - 1, (int) $line['count']);
                }
            }
        }

        return $srcFile;
    }
}
