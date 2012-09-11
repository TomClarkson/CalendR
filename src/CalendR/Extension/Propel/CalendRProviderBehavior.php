<?php

class CalendRProviderBehavior extends Behavior
{
    protected $parameters = array(
        'begin_field' => 'begin',
        'end_field'   => 'end',
    );

    public function queryFilter(&$script)
    {
        $pattern = '/abstract class (\w+)Query extends (\w+) implements (\w+)/i';
        $replace = 'abstract class $1Query extends $2 implements $3, \CalendR\Event\Provider\ProviderInterface';
        if (!preg_match($pattern, $script)) {
            $pattern = '/abstract class (\w+)Query extends (\w+)/i';
            $replace = 'abstract class $1Query extends $2 implements \CalendR\Event\Provider\ProviderInterface';
        }

        $script = preg_replace($pattern, $replace, $script);
    }

    public function queryMethods()
    {
        return <<<EOF
/**
 * Return events that matches to \$begin && \$end
 * \$end date should be exclude
 *
 * @param \DateTime \$begin
 * @param \DateTime \$end
 */
public function getEvents(\\DateTime \$begin, \\DateTime \$end, array \$options = array())
{
    return self::create()
        ->filterByOptions(\$options);
        ->filterByBeginAndEnd(\$begin, \$end)
        ->find()
    ;
}

/**
 * @param DateTime \$begin
 * @param DateTime \$end
 *
 * @return fwEventQuery
 */
public function filterByBeginAndEnd(\\DateTime \$begin, \\DateTime \$end)
{
    return \$this
        ->addBeginDuringEventCondition('begin_during_event', \$begin)
        ->addEndDuringEventCondition('end_during_event', \$end)
        ->addPeriodDuringEventCondition('period_during_event', \$begin, \$end)
        ->addEventDuringPeriod('event_during_period', \$begin, \$end)
        ->where(array('begin_during_event', 'end_during_event', 'period_during_event', 'event_during_period'), 'or')
    ;
}

/**
 * Filter depending to the given options
 *
 * @param array \$options
 */
protected function filterByOptions(array \$options)
{
    return \$this;
}

/**
 * @param \$conditionName
 * @param DateTime \$begin
 *
 * @return fwEventQuery
 */
private function addBeginDuringEventCondition(\$conditionName, \\DateTime \$begin)
{
    return \$this
        ->condition('begin_before_period_begin', 'fwEvent.BeginDate <= ?', \$begin)
        ->condition('end_after_period_begin', 'fwEvent.EndDate >= ?', \$begin)
        ->combine(array('begin_before_period_begin', 'end_after_period_begin'), 'and', \$conditionName)
    ;
}

/**
 * @param \$conditionName
 * @param DateTime \$end
 * @return fwEventQuery
 */
private function addEndDuringEventCondition(\$conditionName, \\DateTime \$end)
{
    return \$this
        ->condition('begin_before_period_end', 'fwEvent.BeginDate <= ?', \$end)
        ->condition('end_after_period_end', 'fwEvent.EndDate >= ?', \$end)
        ->combine(array('begin_before_period_end', 'end_after_period_end'), 'and', \$conditionName)
    ;
}

/**
* @param \$conditionName
* @param \\DateTime \$begin
* @param \\DateTime \$end
* @return fwEventQuery
*/
private function addPeriodDuringEventCondition(\$conditionName, \\DateTime \$begin, \\DateTime \$end)
{
    return \$this
        ->condition('begin_before_period_begin', 'fwEvent.BeginDate <= ?', \$begin)
        ->condition('end_after_period_end', 'fwEvent.EndDate >= ?', \$end)
        ->combine(array('begin_before_period_begin', 'end_after_period_end'), 'and', \$conditionName)
    ;
}

/**
 * @param \$conditionName
 * @param \\DateTime \$begin
 * @param \\DateTime \$end
 * @return fwEventQuery
 */
private function addEventDuringPeriod(\$conditionName, \\DateTime \$begin, \\DateTime \$end)
{
    return \$this
        ->condition('begin_after_period_begin', 'fwEvent.BeginDate >= ?', \$begin)
        ->condition('end_before_period_end', 'fwEvent.EndDate <= ?', \$end)
        ->combine(array('begin_after_period_begin', 'end_before_period_end'), 'and', \$conditionName)
    ;
}
EOF;
    }
}
