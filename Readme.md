# Quill Delta

This is a PHP port of the npm library quill-delta. Right now it only ports the compose function to compose the full history including all the retains and deletes to a final version with only the necessary inserts.

## usage
    
    use Oberon\Quill\Delta\Composer;
        
    $fullOps = [
        [
            "ops" => [
                ["insert" => "hello"],
            ],
        ],
        [
            "ops" => [
                ["retain" => 5],
                ["insert" => " world"],
            ],
        ],
    ];
    
    $quilComposer = new Composer();
    echo $quilComposer->compose($fullOps);
    
    // {"ops":[{"insert":"hello world"}]}
    
or
    
    use Oberon\Quill\Delta\Delta;
    
    $fullOps = [
        [
            "ops" => [
                ["insert" => "hello"],
            ],
        ],
        [
            "ops" => [
                ["retain" => 5],
                ["insert" => " world"],
            ],
        ],
    ];
    
    $output = array_reduce($fullOps, function(Delta $delta, $ops){
        $comp = $delta->compose(new Delta($ops));
        return $comp;
    }, new Delta());
    
    echo $output;
    
    // {"ops":[{"insert":"hello world"}]}