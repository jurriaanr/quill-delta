<?php
/**
 * User: Jurriaan Ruitenberg
 * Date: 24-7-2018
 * Time: 11:12
 */

namespace Oberon\Quill\Delta;

class OpsIterator {
	/** @var array */
	private $ops = [];
	/** @var int */
	private $index = 0;
	/** @var int */
	private $offset = 0;
	
	/**
	 * OpsIterator constructor.
	 *
	 * @param array $ops
	 */
	public function __construct(array $ops) {
		$this->ops = $ops;
	}
	
	public function hasNext() {
		return $this->getPeekLength() < INF;
	}
	
	public function next($length = null) {
		if (!$length) {
			$length = INF;
		}
		
		$nextOp = $this->current();
		
		if ($nextOp) {
			$offset   = $this->offset;
			$opLength = self::length($nextOp);
			
			if ($length >= $opLength - $offset) {
				$length       = $opLength - $offset;
				$this->index  += 1;
				$this->offset = 0;
			}
			else {
				$this->offset += $length;
			}
			if (Delta::isDelete($nextOp)) {
				return [Delta::TYPE_DELETE => $length];
			}
			else {
				$retOp = [];
				
				if (Delta::hasAttributes($nextOp)) {
					$retOp[Delta::KEY_ATTRIBUTES] = $nextOp[Delta::KEY_ATTRIBUTES];
				}
				
				if (Delta::isRetain($nextOp)) {
					$retOp[Delta::TYPE_RETAIN] = $length;
				}
				elseif (Delta::isInsert($nextOp)) {
					$retOp[Delta::TYPE_INSERT] = mb_substr($nextOp[Delta::TYPE_INSERT], $offset, $length, 'UTF-8');
				}
				else {
					// offset should === 0, length should === 1
					$retOp[Delta::TYPE_INSERT] = $nextOp[Delta::TYPE_INSERT];
				}
				
				return $retOp;
			}
		}
		else {
			return [Delta::TYPE_RETAIN => INF];
		}
	}
	
	public function peek() {
		return $this->current();
	}
	
	private function current() {
		return isset($this->ops[$this->index]) ? $this->ops[$this->index] : null;
	}
	
	public function getPeekType() {
		if ($current = $this->current()) {
			if (Delta::isDelete($current)) {
				return Delta::TYPE_DELETE;
			}
			elseif (Delta::isRetain($current)) {
				return Delta::TYPE_RETAIN;
			}
			else {
				return Delta::TYPE_INSERT;
			}
		}
		
		return Delta::TYPE_RETAIN;
	}
	
	public function getPeekLength() {
		if ($current = $this->current()) {
			// Should never return 0 if our index is being managed correctly
			return self::length($current) - $this->offset;
		}
		
		return INF;
	}
	
	/**
	 * @param array $op
	 * @return int
	 */
	private static function length(array $op) {
		if (Delta::isDelete($op)) {
			return $op[Delta::TYPE_DELETE];
		}
		elseif (Delta::isRetain($op)) {
			return $op[Delta::TYPE_RETAIN];
		}
		
		return isset($op[Delta::TYPE_INSERT]) && is_string($op[Delta::TYPE_INSERT]) ? mb_strlen($op[Delta::TYPE_INSERT], 'UTF-8') : 1;
	}
}
