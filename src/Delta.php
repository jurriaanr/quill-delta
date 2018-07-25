<?php
/**
 * User: Jurriaan Ruitenberg
 * Date: 24-7-2018
 * Time: 11:13
 */

namespace Oberon\Quill\Delta;

class Delta
{
    const TYPE_INSERT = 'insert';
    const TYPE_DELETE = 'delete';
    const TYPE_RETAIN = 'retain';
    const KEY_ATTRIBUTES = 'attributes';

    /** @var array */
    private $ops;

    /**
     * Delta constructor.
     * @param array $ops
     */
    public function __construct(array $ops = [])
    {
        if (is_array($ops)) {
            if (isset($ops['ops'])) {
                $this->ops = $ops['ops'];
            } else {
                $this->ops = $ops;
            }
        } else {
            $this->ops = [];
        }
    }

    /**
     * @return array
     */
    public function getOps()
    {
        return $this->ops;
    }

    public function push(array $newOp)
    {
        $index = count($this->ops);
        // JS does this, don't think this is necessary in php
        // newOp = extend(true, {}, newOp);

        if ($index > 0) {
            $lastOp = $this->ops[$index - 1];

            if (self::isDelete($lastOp) && self::isDelete($newOp)) {
                $this->ops[$index - 1] = [
                    self::TYPE_DELETE => $lastOp[self::TYPE_DELETE] + $newOp[self::TYPE_DELETE],
                ];

                return $this;
            }
            // Since it does not matter if we insert before or after deleting at the same index,
            // always prefer to insert first
            if (self::isDelete($lastOp) && self::isInsert($newOp)) {
                $index -= 1;
                if (!isset($this->ops[$index - 1])) {
                    array_unshift($this->ops, $newOp);
                    return $this;
                }
            }

            // could be that this check does not do the same as the original
            if (
                (!self::hasAttributes($newOp) && !self::hasAttributes($lastOp)) ||
                (self::hasAttributes($newOp) && self::hasAttributes($lastOp) && self::equals($newOp[self::KEY_ATTRIBUTES], $lastOp[self::KEY_ATTRIBUTES]))
            ) {
                if (self::isInsert($newOp) && self::isInsert($lastOp)) {
                    $this->ops[$index - 1] = [self::TYPE_INSERT => $lastOp[self::TYPE_INSERT] . $newOp[self::TYPE_INSERT]];
                    if (self::hasAttributes($newOp)) {
                        $this->ops[$index - 1][self::KEY_ATTRIBUTES] = $newOp[self::KEY_ATTRIBUTES];
                    }
                    return $this;
                } elseif (self::isRetain($newOp) && self::isRetain($lastOp)) {
                    $this->ops[$index - 1] = [self::TYPE_RETAIN => $lastOp[self::TYPE_RETAIN] + $newOp[self::TYPE_RETAIN]];
                    if (self::hasAttributes($newOp)) {
                        $this->ops[$index - 1][self::KEY_ATTRIBUTES] = $newOp[self::KEY_ATTRIBUTES];
                    }
                    return $this;
                }
            }
        }
        if ($index === count($this->ops)) {
            $this->ops[] = $newOp;
        } else {
            array_splice($this->ops, $index, 0, $newOp);
        }

        return $this;
    }

    public function compose(Delta $other)
    {
        $thisIter = new OpsIterator($this->getOps());
        $otherIter = new OpsIterator($other->getOps());
        $delta = new Delta();

        while ($thisIter->hasNext() || $otherIter->hasNext()) {
            if ($otherIter->getPeekType() === self::TYPE_INSERT) {
                $delta->push($otherIter->next());
            } elseif ($thisIter->getPeekType() === self::TYPE_DELETE) {
                $delta->push($thisIter->next());
            } else {
                $length = min($thisIter->getPeekLength(), $otherIter->getPeekLength());
                $thisOp = $thisIter->next($length);
                $otherOp = $otherIter->next($length);

                if (self::isRetain($otherOp)) {
                    $newOp = [];

                    if (self::isRetain($thisOp)) {
                        $newOp[self::TYPE_RETAIN] = $length;
                    } else {
                        $newOp[self::TYPE_INSERT] = $thisOp[self::TYPE_INSERT];
                    }

                    // Preserve null when composing with a retain, otherwise remove it for inserts
                    $attributes = Attributes::compose(self::getAttributes($thisOp), self::getAttributes($otherOp), self::isRetain($thisOp));
                    if ($attributes) {
                        $newOp[self::KEY_ATTRIBUTES] = $attributes;
                    }
                    $delta->push($newOp);
                } elseif (self::isDelete($otherOp) && self::isRetain($thisOp)) {
                    // Other op should be delete, we could be an insert or retain
                    // Insert + delete cancels out
                    $delta->push($otherOp);
                }

            }
        }

        return $delta->chop();
    }

    public function chop()
    {
        $count = count($this->ops);
        if ($count > 0) {
            $lastOp = $this->ops[$count - 1];
            if ($lastOp && self::isRetain($lastOp) && !self::hasAttributes($lastOp)) {
                array_pop($this->ops);
            }
        }
        return $this;
    }

    protected function equals($array1, $array2)
    {
        array_multisort($array1);
        array_multisort($array2);
        return (serialize($array1) === serialize($array2));
    }

    public function __toString()
    {
        return json_encode(["ops" => $this->ops], JSON_OBJECT_AS_ARRAY);
    }

    /**
     * @param array $op
     * @return bool
     */
    public static function isDelete(array $op)
    {
        return isset($op[Delta::TYPE_DELETE]) && is_numeric($op[Delta::TYPE_DELETE]);
    }

    /**
     * @param array $op
     * @return bool
     */
    public static function isRetain(array $op)
    {
        return isset($op[Delta::TYPE_RETAIN]) && is_numeric($op[Delta::TYPE_RETAIN]);
    }

    /**
     * @param array $op
     * @return bool
     */
    public static function isInsert(array $op)
    {
        return isset($op[Delta::TYPE_INSERT]) && is_string($op[Delta::TYPE_INSERT]);
    }

    /**
     * @param array $op
     * @return bool
     */
    public static function hasAttributes(array $op)
    {
        return isset($op[self::KEY_ATTRIBUTES]) && is_array($op[self::KEY_ATTRIBUTES]);
    }

    /**
     * @param array $op
     * @return array
     */
    public static function getAttributes(array $op)
    {
        return self::hasAttributes($op) ? $op[self::KEY_ATTRIBUTES] : [];
    }
}
