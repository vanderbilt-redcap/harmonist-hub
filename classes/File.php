<?php

namespace Vanderbilt\HarmonistHubExternalModule;

use Project;
use REDCap;


class File
{
    private $module;
    private $edoc;
    private $icon;
    private $fileSize;
    private $downloadLink;
    private $pdfPath;
    private $storedName;
    private $docName;
    private $currentUserId;

    public function __construct($edoc, $module, $currentUserId = null, $secret_key = null, $secret_iv = null)
    {
        $this->module = $module;
        $this->currentUserId = $currentUserId;
        $this->secret_key = $secret_key;
        $this->secret_iv = $secret_iv;
        $this->fetchFile($edoc);
    }

    public function getPdfPath()
    {
        return $this->pdfPath;
    }

    public function setPdfPath($pdfPath): void
    {
        $this->pdfPath = $pdfPath;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function setIcon($icon): void
    {
        $this->icon = $icon;
    }

    public function getFileSize()
    {
        return $this->fileSize;
    }

    public function setFileSize($fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function getDownloadLink()
    {
        return $this->downloadLink;
    }

    public function setDownloadLink($downloadLink): void
    {
        $this->downloadLink = $downloadLink;
    }

    private function fetchFile($edoc)
    {
        if (isset($edoc) && is_numeric($edoc)) {
            $q = $this->module->query(
                "SELECT stored_name,doc_name,doc_size,mime_type,file_extension FROM redcap_edocs_metadata WHERE doc_id=?",
                [$edoc]
            );
            $row = $q->fetch_array();
            $this->edoc = $edoc;
            $this->storedName = $row['stored_name'];
            $this->docName = $row['doc_name'];
            $this->icon = $this->getFontAwesomeIcon($row['file_extension']);
            $this->fileSize = $this->getSizeFormat($row['doc_size']);
            $this->downloadLink = $this->getDownloadLinkUrl();
            $this->pdfPath = $this->getPdfPathUrl();

        }
    }

    private function getFontAwesomeIcon($fileExtension)
    {
        $icon = "fa-file-o";
        if (strtolower($fileExtension) == '.pdf' || strtolower($fileExtension) == 'pdf') {
            $icon = "fa-file-pdf-o";
        } else {
            if (strtolower($fileExtension) == '.doc' || strtolower($fileExtension) == 'doc' ||
                strtolower($fileExtension) == '.docx' || strtolower($fileExtension) == 'docx') {
                $icon = "fa-file-word-o";
            } else {
                if (strtolower($fileExtension) == '.pptx' || strtolower($fileExtension) == 'pptx' ||
                    strtolower($fileExtension) == '.ppt' || strtolower($fileExtension) == 'ppt') {
                    $icon = "fa-file-powerpoint-o";
                } else {
                    if (strtolower($fileExtension) == '.xlsx' || strtolower($fileExtension) == 'xlsx') {
                        $icon = "fa-file-excel-o";
                    } else {
                        if (strtolower($fileExtension) == '.html' || strtolower($fileExtension) == 'html') {
                            $icon = "fa-file-code-o";
                        } else {
                            if (strtolower($fileExtension) == '.png' || strtolower($fileExtension) == 'png' ||
                                strtolower($fileExtension) == '.jpeg' || strtolower($fileExtension) == 'jpeg' ||
                                strtolower($fileExtension) == '.gif' || strtolower($fileExtension) == 'gif' ||
                                strtolower($fileExtension) == '.tiff' || strtolower($fileExtension) == 'tiff' ||
                                strtolower($fileExtension) == '.bmp' || strtolower($fileExtension) == 'bmp' ||
                                strtolower($fileExtension) == '.jpg' || strtolower($fileExtension) == 'jpg') {
                                $icon = "fa-file-image-o";
                            }
                        }
                    }
                }
            }
        }
        return "<i class='fa " . $icon . "' aria-hidden='true'></i> ";
    }

    private function getSizeFormat($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return "<span style='font-style:italic;font-size:11px;'>(" . round(
                $bytes,
                $precision
            ) . " " . $units[$pow] . ")</span>";
    }

    private function getDownloadLinkUrl()
    {
        return $this->module->getUrl("downloadFile.php") . "&NOAUTH&code=" . getCrypt(
                "sname=" . $this->storedName . "&file=" . urlencode(
                    $this->docName
                ) . "&edoc=" . $this->edoc . "&pid=" . $this->currentUserId,
                'e',
                $this->secret_key,
                $this->secret_iv
            );
    }

    private function getPdfPathUrl()
    {
        return $this->module->getUrl("loadPDF.php") . "&NOAUTH&edoc=" . $this->edoc . "#page=1&zoom=100";
    }
}

?>
