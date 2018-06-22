<?php declare(strict_types=1);

namespace FroshAlgolia\Services\ProductProcessor;

use FroshAlgolia\Structs\Article;
use Shopware\Bundle\StoreFrontBundle\Struct\Product;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use Shopware\Models\Media\Media;

class DefaultProcessor implements ProcessorInterface
{
    /**
     * @param Product              $product     Shopware Product
     * @param Article              $article     Algolia Product
     * @param array                $shopConfig  Shop Configuration
     * @param ShopContextInterface $shopContext
     */
    public function process(Product $product, Article $article, array $shopConfig)
    {
        // Get the media
        $media = $product->getMedia();
        $image = null;

        if (!empty($media)) {
            /** @var Media $mediaObject */
            $mediaObject = current($media);
            $image = $mediaObject->getThumbnail(0)->getSource();
        }

        // Get the votes
        $voteAvgPoints = 0;
        $votes = $product->getVoteAverage();
        if ($votes) {
            $voteAvgPoints = (int) $votes->getPointCount()[0]['points'];
        }

        // Build the algolia product
        $article->setObjectID($product->getNumber());
        $article->setArticleId($product->getId());
        $article->setName($product->getName());
        $article->setNumber($product->getNumber());
        $article->setManufacturerName($product->getManufacturer()->getName());
        $article->setPrice(round($product->getCheapestPrice()->getCalculatedPrice(), 2));
        $article->setDescription(strip_tags($product->getShortDescription()));
        $article->setEan($product->getEan());
        $article->setImage($image);
        $article->setCategories($product->getAttribute('categories')->jsonSerialize());
        $article->setAttributes($this->getAttributes($product, $shopConfig));
        $article->setProperties($this->getProperties($product));
        $article->setSales($product->getSales());
        $article->setVotes($votes);
        $article->setVoteAvgPoints($voteAvgPoints);
    }

    /**
     * Get all product attributes.
     *
     * @param Product $product
     *
     * @return array
     */
    private function getAttributes(Product $product, $shopConfig)
    {
        $data = [];

        if (!isset($product->getAttributes()['core'])) {
            return [];
        }

        $attributes = $product->getAttributes()['core']->toArray();
        $blockedAttributes = array_column($shopConfig['blockedAttributes'], 'name');

        foreach ($attributes as $key => $value) {
            // Skip this attribute if it´s on the list of blocked attributes
            if (in_array($key, $blockedAttributes) || empty($value)) {
                continue;
            }

            // Map value to data array
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Fetches all product properties as an array.
     *
     * @param Product $product
     *
     * @return array
     */
    private function getProperties(Product $product)
    {
        $properties = [];

        if ($set = $product->getPropertySet()) {
            $groups = $set->getGroups();

            foreach ($groups as $group) {
                $options = $group->getOptions();

                foreach ($options as $option) {
                    $properties[$group->getName()] = $option->getName();
                }
            }
        }

        return $properties;
    }
}
