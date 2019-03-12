<?php

/**
 * Paginator class.
 *
 * Used to create a set of links for pagination.
 */
class Paginator
{
    /** @var string $url URL to which pagination links should point at.
     *
     * Should contain the substring __PAGE__ as a placeholder for where the
     * page numbers will be inserted.
     */
    private $url;

    /** @var int $limit Number of items per page */
    private $limit;

    /** @var int $page Current page */
    private $page;

    /** @var int $total Total number of items to paginate */
    private $total;

    /** @var int $totalPages Total number of pages, as determined by
     * total number of items and number of items per page.
     */
    protected $totalPages;


    /**
     * Store values used to construct the links.
     */
    public function __construct(string $url, $page, $limit, $total)
    {
        $this->url = $url;
        $this->page = (int) $page;
        $this->limit = (int) $limit;
        $this->total = (int) $total;
        $this->totalPages = $this->getTotalPages();
    }


    /**
     * Calculate offset with which to query database.
     *
     * Calculate offset based on current page and number of items
     * per page.
     *
     * @return int Offset
     */
    public function getOffset()
    {
        return (int) ($this->page - 1) * $this->limit;
    }


    /**
     * Calculate total number of pages required.
     *
     * Calculate total number of pages required to list all items,
     * given the desired number of items per page.
     *
     * @return int Total number of pages
     */
    public function getTotalPages()
    {
        return (int) ceil($this->total / $this->limit);
    }


    /**
     * Get paginated links with list of numbers for navigating pages.
     *
     * @return string Links as HTML.
     */
    public function numberedLinks()
    {
        $html = '<ul class="pagination">';

        for ($page=1; $page <= $this->totalPages; $page++) {
            $html .= '<li class="pagination-item">';
            if ($page === $this->page) {
                $html .= '<span class="pagination-active">' . $page . '</span>';
            } else {
                $pageUrl = str_replace('__page__', $page, $this->url);
                $html .= '<a href="' . $pageUrl . '" class="pagination-link">' . $page . '</a>';
            }
            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
    }


    /**
     * Get paginated links with arrows for navigating pages.
     *
     * @return string Links as HTML.
     */
    public function shortenedLinks()
    {
        $html = '<ul class="pagination">';

        $html .= $this->firstLink();
        $html .= $this->previousLink();
        $html .= $this->currentLink();
        $html .= $this->nextLink();
        $html .= $this->lastLink();

        $html .= '</ul>';

        return $html;
    }


    /**
     * <li> tag for current page.
     *
     * @return string HTML <li> tag.
     */
    private function currentLink()
    {
        return '<li class="pagination-item"><span class="pagination-active">' . $this->page . '</span></li>';
    }


    /**
     * <li> tag for first page.
     *
     * @return string HTML <li> tag.
     */
    private function firstLink()
    {
        $html = '<li class="pagination-item">';
        if ($this->page === 1) {
            $html .= '<span class="pagination-disabled">First</span>';
        } else {
            $html .= '<a href="' . str_replace('__page__', 1, $this->url) . '" class="pagination-link">First</a>';
        }
        $html .= '</li>';
        return $html;
    }


    /**
     * <li> tag for last page.
     *
     * @return string HTML <li> tag.
     */
    private function lastLink()
    {
        $html = '<li class="pagination-item">';
        if ($this->page === $this->totalPages) {
            $html .= '<span class="pagination-disabled">Last</span>';
        } else {
            $html .= '<a href="' . str_replace('__page__', $this->totalPages, $this->url) . '" class="pagination-link">Last</a>';
        }
        $html .= '</li>';
        return $html;
    }


    /**
     * <li> tag for previous page.
     *
     * @return string HTML <li> tag.
     */
    private function previousLink()
    {
        $html = '<li class="pagination-item">';
        if ($this->page === 1) {
            $html .= '<span class="pagination-disabled">&laquo;</span>';
        } else {
            $html .= '<a href="' . str_replace('__page__', $this->page - 1, $this->url) . '" class="pagination-link">&laquo;</a>';
        }
        $html .= '</li>';
        return $html;
    }


    /**
     * <li> tag for next page.
     *
     * @return string HTML <li> tag.
     */
    private function nextLink()
    {
        $html = '<li class="pagination-item">';
        if ($this->page === $this->totalPages) {
            $html .= '<span class="pagination-disabled">&raquo;</span>';
        } else {
            $html .= '<a href="' . str_replace('__page__', $this->page + 1, $this->url) . '" class="pagination-link">&raquo;</a>';
        }
        $html .= '</li>';
        return $html;
    }

}
